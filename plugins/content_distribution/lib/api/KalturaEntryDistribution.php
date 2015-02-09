<?php
/**
 * @package plugins.contentDistribution
 * @subpackage api.objects
 */
class KalturaEntryDistribution extends KalturaObject implements IFilterable
{
	/**
	 * Auto generated unique id
	 * 
	 * @readonly
	 * @var int
	 * @filter eq,in
	 */
	public $id;

	/**
	 * Entry distribution creation date as Unix timestamp (In seconds)
	 * 
	 * @var time
	 * @readonly
	 * @filter gte,lte,order
	 */
	public $createdAt;

	/**
	 * Entry distribution last update date as Unix timestamp (In seconds)
	 * 
	 * @var time
	 * @readonly
	 * @filter gte,lte,order
	 */
	public $updatedAt;

	/**
	 * Entry distribution submission date as Unix timestamp (In seconds)
	 * 
	 * @var time
	 * @readonly
	 * @filter gte,lte,order
	 */
	public $submittedAt;

	/**
	 * @insertonly
	 * @var string
	 * @filter eq,in
	 */
	public $entryId;

	/**
	 * @readonly
	 * @var int
	 */
	public $partnerId;

	/**
	 * @insertonly
	 * @var int
	 * @filter eq,in
	 */
	public $distributionProfileId;

	/**
	 * @readonly
	 * @var KalturaEntryDistributionStatus
	 * @filter eq,in
	 */
	public $status;

	/**
	 * @readonly
	 * @var KalturaEntryDistributionSunStatus
	 */
	public $sunStatus;

	/**
	 * @readonly
	 * @var KalturaEntryDistributionFlag
	 * @filter eq,in
	 */
	public $dirtyStatus;

	/**
	 * Comma separated thumbnail asset ids
	 * @var string
	 */
	public $thumbAssetIds;

	/**
	 * Comma separated flavor asset ids
	 * @var string
	 */
	public $flavorAssetIds;
	
	/**
	 * Comma separated asset ids
	 * @var string
	 */
	public $assetIds;
	
	/**
	 * Entry distribution publish time as Unix timestamp (In seconds)
	 * 
	 * @var time
	 * @filter gte,lte,order
	 */
	public $sunrise;

	/**
	 * Entry distribution un-publish time as Unix timestamp (In seconds)
	 * 
	 * @var time
	 * @filter gte,lte,order
	 */
	public $sunset;

	/**
	 * The id as returned from the distributed destination
	 * @readonly
	 * @var string
	 */
	public $remoteId;

	/**
	 * The plays as retrieved from the remote destination reports
	 * @readonly
	 * @var int
	 */
	public $plays;

	/**
	 * The views as retrieved from the remote destination reports
	 * @readonly
	 * @var int
	 */
	public $views;

	/**
	 * @var KalturaDistributionValidationErrorArray
	 */
	public $validationErrors;

	/**
	 * @var KalturaBatchJobErrorTypes
	 * @readonly
	 */
	public $errorType;

	/**
	 * @var int
	 * @readonly
	 */
	public $errorNumber;

	/**
	 * @var string
	 * @readonly
	 */
	public $errorDescription;

	/**
	 * @var KalturaNullableBoolean
	 * @readonly
	 */
	public $hasSubmitResultsLog;

	/**
	 * @var KalturaNullableBoolean
	 * @readonly
	 */
	public $hasSubmitSentDataLog;

	/**
	 * @var KalturaNullableBoolean
	 * @readonly
	 */
	public $hasUpdateResultsLog;

	/**
	 * @var KalturaNullableBoolean
	 * @readonly
	 */
	public $hasUpdateSentDataLog;

	/**
	 * @var KalturaNullableBoolean
	 * @readonly
	 */
	public $hasDeleteResultsLog;

	/**
	 * @var KalturaNullableBoolean
	 * @readonly
	 */
	public $hasDeleteSentDataLog;
	
	/*
	 * mapping between the field on this object (on the left) and the setter/getter on the object (on the right)  
	 */
	private static $map_between_objects = array 
	 (
		'id',
		'createdAt',
		'updatedAt',
		'entryId',
		'partnerId',
		'distributionProfileId',
		'status',
		'dirtyStatus',
		'thumbAssetIds',
		'flavorAssetIds',
	 	'assetIds',
		'sunrise',
		'sunset',
		'remoteId',
		'plays',
		'views',
		'errorType',
		'errorNumber',
		'errorDescription',
	 );
		 
	public function getMapBetweenObjects()
	{
		return array_merge(parent::getMapBetweenObjects(), self::$map_between_objects);
	}
	
	public function toObject($dbObject = null, $skip = array())
	{
		if (is_null($dbObject))
			return new EntryDistribution();
			
		return parent::toObject($dbObject, $skip);
	}
	
	public function fromObject($sourceObject, IResponseProfile $responseProfile = null)
	{
		if(!$sourceObject)
			return;
			
		parent::fromObject($sourceObject, $responseProfile);
		
		$this->sunStatus = $sourceObject->getSunStatus();
		$this->sunrise = $sourceObject->getSunrise(null);
		$this->sunset = $sourceObject->getSunset(null);
		$this->submittedAt = $sourceObject->getSubmittedAt(null);
            
		$this->validationErrors = KalturaDistributionValidationErrorArray::fromDbArray($sourceObject->getValidationErrors());
		
		$this->hasSubmitResultsLog = (bool)$sourceObject->getSubmitResultsVersion();
		$this->hasSubmitSentDataLog = (bool)$sourceObject->getSubmitDataVersion();
		$this->hasUpdateResultsLog = (bool)$sourceObject->getUpdateResultsVersion();
		$this->hasUpdateSentDataLog = (bool)$sourceObject->getUpdateDataVersion();
		$this->hasDeleteResultsLog = (bool)$sourceObject->getDeleteResultsVersion();
		$this->hasDeleteSentDataLog = (bool)$sourceObject->getDeleteDataVersion();
	}

	public function getExtraFilters()
	{
		return array(
		);
	}
	
	public function getFilterDocs()
	{
		return array(
		);
	}
	
	public function validateForInsert($propertiesToSkip = array())
	{
		$this->validatePropertyNotNull("entryId");
		$this->validatePropertyNotNull("distributionProfileId");
		parent::validateForInsert($propertiesToSkip);
	}
}