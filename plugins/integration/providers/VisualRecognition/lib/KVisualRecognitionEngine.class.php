<?php
/**
 * @package plugins.visualRecognition
 * @subpackage Scheduler
 */
class KVisualRecognitionEngine implements KIntegrationCloserEngine
{
    
    const AUTOMATIC_VISUAL_RECOGNITION_TAG = 'origin_visual_recognition';
    
	/* (non-PHPdoc)
	 * @see KIntegrationCloserEngine::dispatch()
	 */
	public function dispatch(KalturaBatchJob $job, KalturaIntegrationJobData &$data)
	{
		KalturaLog::info("BUGA ".__FUNCTION__." dispatching Recognotion");
		return $this->doDispatch($job, $data, $data->providerData);
	}

	/* (non-PHPdoc)
	 * @see KIntegrationCloserEngine::close()
	 */
	public function close(KalturaBatchJob $job, KalturaIntegrationJobData &$data)
	{
		KalturaLog::info("BUGA ".__FUNCTION__." visual Recognotion");

		return $this->doClose($job, $data, $data->providerData);
	}

	protected function doDispatch(KalturaBatchJob $job, KalturaIntegrationJobData &$data, KalturaVisualRecognitionJobProviderData $providerData)
	{
		KalturaLog::info("BUGA " . __FUNCTION__ . " Dispatching Visual Recognition");

		if (!empty($job->entryId)) {
			KBatchBase::impersonate($job->partnerId);
			$entry = KBatchBase::$kClient->baseEntry->get($job->entryId);
			
			if (!($entry instanceof KalturaMediaEntry) && isset($entry->duration))
            {
				throw new Exception("Invalid data type expected video");
            }
		}

		$thumbnailURLs = BaseDetectionEngine::getThumbnailUrls($entry->thumbnailUrl, $entry->duration, $providerData->thumbInterval);
		
        // first run all the async detectors
		$cloudEngine = new CloudsapiDetectionEngine();

        KalturaLog::info("BUGA " . __FUNCTION__ . " thumbnail url = ".print_r($thumbnailURLs, true));
        $jobs = $cloudEngine->initiateRecognition($thumbnailURLs);
        KalturaLog::info("BUGA " . __FUNCTION__ . " return value  = ".print_r($jobs, true));
        
        $data->providerData->externalJobs = $this->arrayToKeyValArray($jobs);
        
        $clarifaiEngine = new ClarifaiDetectionEngine();
        $initResult = $clarifaiEngine->init();
        if ($initResult == true) {
            KalturaLog::info("got valid result on auth initiation from clarifi");

            $timelineMetadata = $clarifaiEngine->initiateRecognition($thumbnailURLs);
             KalturaLog::info("results from ClarifaiDetectionEngine " .print_r($timelineMetadata, true));
            $this->createThumbCuePoint($job->partnerId, $timelineMetadata, $job->entryId);
        } else {
            KalturaLog::info("got BAD result on auth initiation from clarifi");
        }
        
        
        
        KalturaLog::info("adult policy is ".$providerData->adultContentPolicy );
        // auto moderation work
        if($providerData->adultContentPolicy != KalturaVisualRecognitionAdultContentPolicy::IGNORE)
        {
            KalturaLog::info(" so checking content with sight");
            $sightEngine = new SightDetectionEngine();
            $isInappropriate = $sightEngine->initiateRecognition($thumbnailURLs);
            if($isInappropriate)
            {
                KalturaLog::info("sight says content is inappropriate");
                switch($providerData->adultContentPolicy)
                {
                    case KalturaVisualRecognitionAdultContentPolicy::AUTO_REJECT:
                        KBatchBase::$kClient->baseEntry->reject($job->entryId);
                        break;
                    case KalturaVisualRecognitionAdultContentPolicy::AUTO_FLAG:
                        $flag = new KalturaModerationFlag();
                        $flag->flaggedEntryId = $job->entryId;
                        $flag->flagType = KalturaModerationFlagType::SEXUAL_CONTENT;
                        KBatchBase::$kClient->baseEntry->flag($flag);
                        break;
                    case KalturaVisualRecognitionAdultContentPolicy::IGNORE:
                    default:
                        KalturaLog::info("could not match any adult content policy, so not doing anything");
                        // do nothing
                        break;
                }
            }
            else
            {
                KalturaLog::info("sight says content is fine");
            }
        }
        KBatchBase::unimpersonate();
		// To finish, return true
		// To wait for closer, return false
		// To fail, throw exception


		return false;
	}

	protected function doClose(KalturaBatchJob $job, KalturaIntegrationJobData &$data, KalturaVisualRecognitionJobProviderData $providerData)
	{
		KalturaLog::info("BUGA ".__FUNCTION__." Thumbnail interval [$providerData->thumbInterval]");

		$cloudEngine = new CloudsapiDetectionEngine();
		$cloudEngine->init();

		// To finish, return true
		// To keep open for future closer, return false
		// To fail, throw exception
        
        $jobIds = $this->keyValArrayToArray($providerData->externalJobs);
        KalturaLog::info("BUGA ".__FUNCTION__." checking job ".$providerData->externalJobs);
        $results = $cloudEngine->checkRecognitionStatus($jobIds);
        if ($results === false )
        {
            // job not closed, wait for another closer
            KalturaLog::info("SUSU ".print_r($results, true));
            return false;
        } else {
            // return true to close the job
            KalturaLog::info("Results from CloudsapiDetectionEngine is ".print_r($results, true));
            // create cuepoints from array of results
            $this->createThumbCuePoint($job->partnerId, $results, $job->entryId);
            return true;
        }
	}
    
    public function createThumbCuePoint($partnerId, array $thumbCuePointsInitData, $entryId) {
        if (!empty($thumbCuePointsInitData)) {
            KBatchBase::impersonate($partnerId);
            
            $filter = new KalturaThumbCuePointFilter();
            $filter->orderBy = KalturaCuePointOrderBy::START_TIME_ASC;
            $filter->tagsLike = self::AUTOMATIC_VISUAL_RECOGNITION_TAG;
            $filter->subTypeEqual = ThumbCuePointSubType::SLIDE;
            $filter->entryIdEqual = $entryId;
            $pager = new KalturaFilterPager();
            $pager->pageSize = 500;
            $cuepoints = KBatchBase::$kClient->cuePoint->listAction($filter, $pager);
            $existingCuePoints = array();
            foreach($cuepoints->objects as $cuepoint)
            {
                $existingCuePoints[$cuepoint->startTime] = array('id' => $cuepoint->id, 'desc' => $cuepoint->description);
            }
            
            KalturaLog::info(print_r($existingCuePoints, true));
            
            
            KBatchBase::$kClient->startMultiRequest();
            foreach ($thumbCuePointsInitData as $sec => $thumbCuePointInitData) {
                $startTime = $sec*1000;
                KalturaLog::info("starttime is set to $startTime");
                $cuePointTmp = new KalturaThumbCuePoint();
                
                if(is_array($thumbCuePointInitData))
                {
                    $cuePointTmp->description = implode(' ', $thumbCuePointInitData);
                }
                else
                {
                    KalturaLog::info("thumbcuepoints data is not array ".print_r($thumbCuePointInitData));
                }
                
                // if there is already a cuepoint from another engine - concat results
                if(isset($existingCuePoints[$startTime]))
                {
                    KalturaLog::info("fonud existing cuepoint at position $startTime with ID ".$existingCuePoints[$startTime]['id']);
                    KalturaLog::info("updating cuepoint ".$existingCuePoints[$startTime]['id']." in sec $sec");
                    $cuePointTmp->description = $cuePointTmp->description . ' ' . $existingCuePoints[$startTime]['desc'];
                    KBatchBase::$kClient->cuePoint->update($existingCuePoints[$startTime]['id'], $cuePointTmp);
                }
                else
                {
                    KalturaLog::info("adding cuepoint in sec $sec");
                    // create cuepoint as one does not exist on startTime
                    $cuePointTmp->entryId = $entryId;
                    $cuePointTmp->startTime = $startTime;
                    $cuePointTmp->subType = KalturaThumbCuePointSubType::SLIDE;
                    $cuePointTmp->tags = self::AUTOMATIC_VISUAL_RECOGNITION_TAG;
                    KBatchBase::$kClient->cuePoint->add($cuePointTmp);
                }
            }
            KBatchBase::$kClient->doMultiRequest();
            KBatchBase::unimpersonate();
        }
    }
    
    private function keyValArrayToArray($keyValArray)
    {
        $array = array();
        foreach($keyValArray as $keyVal)
        {
            if(is_object($keyVal) && $keyVal instanceof KalturaKeyValue)
            {
                $array[$keyVal->key] = $keyVal->value;
            }
            else
            {
                throw new Exception("element is not KalturaKeyValue cannot be casted back to regular array");
            }
        }
        
        return $array;
    }
    
    private function arrayToKeyValArray($array)
    {
        $keyValArray = array();
        foreach($array as $key => $value)
        {
            $keyVal = new KalturaKeyValue();
            $keyVal->key = $key;
            $keyVal->value = $value;
            $keyValArray[] = $keyVal;
        }
        return $keyValArray;
    }
}
