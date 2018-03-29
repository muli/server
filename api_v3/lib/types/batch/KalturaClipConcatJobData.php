<?php
/**
 * Created by IntelliJ IDEA.
 * User: roie.beck
 * Date: 3/12/2018
 * Time: 11:20 AM
 */

class KalturaClipConcatJobData extends KalturaJobData
{

	/**$destEntryId
	 * @var string
	 */
	public $destEntryId;

	/**$destEntryId
	 * @var string
	 */
	public $cuePointDestEntryId;

	/**$tempEntryId
	 * @var string
	 */
	public $tempEntryId;

	/**$tempEntryId
	 * @var string
	 */
	public $sourceEntryId;

	/** $partnerId
	 * @var int
	 */
	public $partnerId;

	/** $priority
	 * @var int
	 */
	public $priority;


	/** clip operations
	 * @var KalturaObjectArray $operationAttributes
	 */
	public $operationAttributes;


	private static $map_between_objects = array
	(
		'destEntryId',
		'tempEntryId',
		'partnerId',
		'priority',
		'operationAttributes',
		'sourceEntryId',
		'cuePointDestEntryId'
	);

	/* (non-PHPdoc)
 * @see KalturaObject::getMapBetweenObjects()
 */
	public function getMapBetweenObjects ( )
	{
		return array_merge ( parent::getMapBetweenObjects() , self::$map_between_objects );
	}

	/* (non-PHPdoc)
	 * @see KalturaObject::toObject()
	 */
	public function toObject($dbData = null, $props_to_skip = array())
	{
		if(is_null($dbData))
			$dbData = new kClipConcatJobData();

		return parent::toObject($dbData, $props_to_skip);
	}
}