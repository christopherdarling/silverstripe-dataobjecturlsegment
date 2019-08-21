<?php

namespace ChristopherDarling\DataObjectURLSegment;

use SilverStripe\ORM\DataList;
use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\Parsers\URLSegmentFilter;
use ChristopherDarling\DataObjectURLSegment\DataObjectURLSegmentField;

class DataObjectExtension extends DataExtension
{
    private static $db = [
        'URLSegment' => 'Varchar(255)',
    ];

    private static $indexes = [
        'URLSegment' => true,
    ];

    private static $field_labels = array(
        'URLSegment' => 'URL'
    );


    public function updateCMSFields(FieldList $fields)
    {
        $baseLink = Director::absoluteBaseURL();

        $urlsegment = DataObjectURLSegmentField::create("URLSegment", $this->owner->fieldLabel('URLSegment'))
            ->setModelRecord($this->owner)
            ->setURLPrefix($baseLink)
            ->setDefaultURL($this->generateURLSegment(_t(
                'SilverStripe\\CMS\\Controllers\\CMSMain.NEWPAGE',
                'New {pagetype}',
                array('pagetype' => $this->owner->i18n_singular_name())
            )));

        if (!URLSegmentFilter::create()->getAllowMultibyte()) {
            $helpText = _t('SilverStripe\\CMS\\Forms\\SiteTreeURLSegmentField.HelpChars', ' Special characters are automatically converted or removed.');
            $urlsegment->setHelpText($helpText);
        }

        $fields->insertAfter('Title', $urlsegment);
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // If there is no URLSegment set, generate one from Title
        $defaultSegment = $this->generateURLSegment(_t(
            'SilverStripe\\CMS\\Controllers\\CMSMain.NEWPAGE',
            'New {pagetype}',
            array('pagetype' => $this->owner->i18n_singular_name())
        ));
        if ((!$this->owner->URLSegment || $this->owner->URLSegment == $defaultSegment) && $this->owner->Title) {
            $this->owner->URLSegment = $this->generateURLSegment($this->owner->Title);
        } elseif ($this->owner->isChanged('URLSegment', 2)) {
            // Do a strict check on change level, to avoid double encoding caused by
            // bogus changes through forceChange()
            $filter = URLSegmentFilter::create();
            $this->owner->URLSegment = $filter->filter($this->owner->URLSegment);
            // If after sanitising there is no URLSegment, give it a reasonable default
            if (!$this->owner->URLSegment) {
                $this->owner->URLSegment = __CLASS__ . "-$this->ID";
            }
        }

        // Ensure that this object has a non-conflicting URLSegment value.
        $count = 2;
        while (!$this->validURLSegment()) {
            $this->owner->URLSegment = preg_replace('/-[0-9]+$/', null, $this->owner->URLSegment) . '-' . $count;
            $count++;
        }
    }

    /**
     * Returns true if this object has a URLSegment value that does not conflict with any other objects. This method
     * checks for:
     *  - A page with the same URLSegment that has a conflict
     *  - Conflicts with actions on the parent page
     *  - A conflict caused by a root page having the same URLSegment as a class name
     *
     * @return bool
     */
    public function validURLSegment()
    {
        // Check for clashing pages by url, id, and parent
        $source = DataList::create($this->owner->ClassName)->filter('URLSegment', $this->owner->URLSegment);
        if ($this->owner->ID) {
            $source = $source->exclude('ID', $this->owner->ID);
        }
        return !$source->exists();
    }

    /**
     * Generate a URL segment based on the title provided.
     *
     * If {@link Extension}s wish to alter URL segment generation, they can do so by defining
     * updateURLSegment(&$url, $title).  $url will be passed by reference and should be modified. $title will contain
     * the title that was originally used as the source of this generated URL. This lets extensions either start from
     * scratch, or incrementally modify the generated URL.
     *
     * @param string $title Page title
     * @return string Generated url segment
     */
    public function generateURLSegment($title)
    {
        $filter = URLSegmentFilter::create();
        $filteredTitle = $filter->filter($title);


        // Fallback to generic page name if path is empty (= no valid, convertable characters)
        if (!$filteredTitle || $filteredTitle == '-' || $filteredTitle == '-1') {
            $filteredTitle = __CLASS__ . "-$this->ID";
        }

        // Hook for extensions
        $this->owner->extend('updateURLSegment', $filteredTitle, $title);

        return $filteredTitle;
    }
}
