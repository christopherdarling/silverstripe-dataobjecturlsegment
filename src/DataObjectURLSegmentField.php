<?php

namespace ChristopherDarling\DataObjectURLSegment;

use SilverStripe\ORM\DataObject;
use SilverStripe\View\Requirements;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Forms\SiteTreeURLSegmentField;

class DataObjectURLSegmentField extends SiteTreeURLSegmentField
{
    protected $record;

    public function setModelRecord(DataObject $record)
    {
        $this->record = $record;
        return $this;
    }

    /**
     * @return DataObject
     */
    public function getPage()
    {
        return $this->record;
    }
}
