<?php

namespace ChristopherDarling\DataObjectURLSegment;

use SilverStripe\ORM\DataObject;
use SilverStripe\View\Requirements;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Forms\SiteTreeURLSegmentField;

class DataObjectURLSegmentField extends SiteTreeURLSegmentField
{
    public function Field($properties = array())
    {
        Requirements::add_i18n_javascript('silverstripe/cms: client/lang', false, true);

        return parent::Field($properties);
    }

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
