<?php

namespace Rcm\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Rcm\Exception\InvalidArgumentException;
use Rcm\Tracking\Model\Tracking;
use Rcm\Tracking\Model\TrackingAbstract;
use Reliv\RcmApiLib\Model\ApiPopulatableInterface;
use Reliv\RcmApiLib\Model\ApiSerializableInterface;

/**
 * Container Abstract.  Contains methods shared by container classes
 *
 * Abstract for containers.  This class defines shared methods and properties for
 * container classes.  Please note that if using doctrine the properties need to
 * still be defined by the actual class as well.
 *
 * @category  Reliv
 * @package   Rcm
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class ContainerAbstract extends TrackingAbstract implements ContainerInterface, Tracking
{
    /**
     * @var string Container name
     */
    protected $name;

    /**
     * @var string Authors name
     */
    protected $author;

    /**
     * @var \DateTime Date page was last published
     */
    protected $lastPublished;

    /**
     * @var \Rcm\Entity\Revision Integer published Page Revision
     */
    protected $publishedRevision;

    /**
     * @var int
     */
    protected $publishedRevisionId;

    /**
     * @var \Rcm\Entity\Revision Integer Staged Revision
     */
    protected $stagedRevision;

    /**
     * @var int
     */
    protected $stagedRevisionId;

    /**
     * @var \Rcm\Entity\Site
     **/
    protected $site;

    /**
     * @var int
     */
    protected $siteId;

    /**
     * @var array|\Doctrine\Common\Collections\ArrayCollection
     */
    protected $revisions;

    /**
     * @var Revision Used to store the current displayed revision
     */
    protected $currentRevision;

    /**
     * @var Revision Place Holder for last saved draft
     */
    protected $lastSavedDraft;

    /**
     * @param string $createdByUserId <tracking>
     * @param string $createdReason   <tracking>
     */
    public function __construct(
        string $createdByUserId,
        string $createdReason = Tracking::UNKNOWN_REASON
    ) {
        parent::__construct($createdByUserId, $createdReason);
    }

    /**
     * Get a clone with special logic
     * Any copy will be changed to staged
     *
     * @param string $createdByUserId
     * @param string $createdReason
     *
     * @return ContainerInterface|ContainerAbstract
     */
    public function newInstance(
        string $createdByUserId,
        string $createdReason = Tracking::UNKNOWN_REASON
    ) {
        /** @var ContainerInterface|ContainerAbstract $new */
        $new = parent::newInstance(
            $createdByUserId,
            $createdReason
        );

        $new->lastPublished = new \DateTime();

        $new->revisions = new ArrayCollection();

        if (!empty($new->publishedRevision)) {
            $revision = $new->publishedRevision->newInstance(
                $createdByUserId,
                $createdReason
            );
            $new->removePublishedRevision();
            $new->revisions->add($revision);
            $new->setStagedRevision($revision);
        } elseif (!empty($new->stagedRevision)) {
            $revision = $new->stagedRevision->newInstance(
                $createdByUserId,
                $createdReason
            );
            $new->setStagedRevision($revision);
            $new->revisions->add($revision);
        }

        return $new;
    }

    /**
     * This is used mainly for site copies to eliminate pages that are not published
     *
     * @param string $createdByUserId
     * @param string $createdReason
     *
     * @return null|ContainerAbstract|ContainerInterface
     */
    public function newInstanceIfHasRevision(
        string $createdByUserId,
        string $createdReason = Tracking::UNKNOWN_REASON
    ) {
        /** @var ContainerInterface|ContainerAbstract $new */
        $new = parent::newInstance(
            $createdByUserId,
            $createdReason
        );

        $publishedRevision = $new->getPublishedRevision();

        if (empty($publishedRevision)) {
            return null;
        }

        $new->lastPublished = new \DateTime();

        $new->revisions = new ArrayCollection();
        $new->stagedRevision = null;
        $new->stagedRevisionId = null;

        $new->setPublishedRevision(
            $publishedRevision->newInstance(
                $createdByUserId,
                $createdReason
            )
        );

        return $new;
    }

    /**
     * Gets the Name property
     *
     * @return string Name
     *
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the Name property
     *
     * @param string $name Name of Page.  Should be URL friendly and should not
     *                     included spaces.
     *
     * @return void
     *
     * @throws InvalidArgumentException Exception thrown if name contains spaces.
     */
    public function setName($name)
    {
        //Check for spaces.  Throw exception if spaces are found.
        if (strpos($name, ' ')) {
            throw new InvalidArgumentException(
                'Container Names should not contain spaces.'
            );
        }

        $this->name = $name;
    }

    /**
     * Gets the Author property
     *
     * @return string Author
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Sets the Author property
     *
     * @param string $author ID of Author.
     *
     * @return void
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * Gets the CreatedDate property
     *
     * @return \DateTime CreatedDate
     *
     */
    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    /**
     * getCreatedDateString
     *
     * @param string $format
     *
     * @return null|string
     */
    public function getCreatedDateString($format = \DateTime::ISO8601)
    {
        $date = $this->getCreatedDate();

        if (empty($date)) {
            return null;
        }

        return $date->format($format);
    }

    /**
     * Gets the LastPublished property
     *
     * @return \DateTime LastPublished
     */
    public function getLastPublished()
    {
        return $this->lastPublished;
    }

    /**
     * Sets the LastPublished property
     *
     * @param \DateTime $lastPublished Date the page was last published.
     *
     * @return void
     */
    public function setLastPublished(\DateTime $lastPublished)
    {
        $this->lastPublished = $lastPublished;
    }

    /**
     * getLastPublishedString
     *
     * @param string $format
     *
     * @return null|string
     */
    public function getLastPublishedString($format = \DateTime::ISO8601)
    {
        $date = $this->getLastPublished();

        if (empty($date)) {
            return null;
        }

        return $date->format($format);
    }

    /**
     * Get Published Revision
     *
     * @return \Rcm\Entity\Revision
     */
    public function getPublishedRevision()
    {
        return $this->publishedRevision;
    }

    /**
     * Set the published published revision for the page
     *
     * @param Revision $revision Revision object to add
     *
     * @return void
     */
    public function setPublishedRevision(Revision $revision)
    {
        if (!empty($this->stagedRevision)) {
            $this->removeStagedRevision();
        }

        $revision->publishRevision();
        $this->publishedRevision = $revision;
        $this->publishedRevisionId = $revision->getRevisionId();
        $this->setLastPublished(new \DateTime());
    }

    /**
     * getPublishedRevisionId
     *
     * @return int
     */
    public function getPublishedRevisionId()
    {
        return $this->publishedRevisionId;
    }

    /**
     * Gets the Staged revision
     *
     * @return Revision Staged Revision
     */
    public function getStagedRevision()
    {
        return $this->stagedRevision;
    }

    /**
     * Sets the staged revision
     *
     * @param Revision $revision Revision object to add
     *
     * @return void
     */
    public function setStagedRevision(Revision $revision)
    {
        if (!empty($this->publishedRevision)
            && $this->publishedRevision->getRevisionId() == $revision->getRevisionId()
        ) {
            $this->removePublishedRevision();
        }

        $this->stagedRevision = $revision;
        $this->stagedRevisionId = $revision->getRevisionId();
    }

    /**
     * getStagedRevisionId
     *
     * @return mixed
     */
    public function getStagedRevisionId()
    {
        return $this->stagedRevisionId;
    }

    /**
     * Remove Published Revision
     */
    public function removePublishedRevision()
    {
        $this->publishedRevision = null;
        $this->publishedRevisionId = null;
    }

    /**
     * Remove Staged Revision
     *
     * @return void
     */
    public function removeStagedRevision()
    {
        $this->stagedRevision = null;
        $this->stagedRevisionId = null;
    }

    /**
     * Get the site that uses this page.
     *
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Set site the page belongs to
     *
     * @param Site $site Site object to add
     *
     * @return void
     */
    public function setSite(Site $site)
    {
        $this->site = $site;
        $this->siteId = $site->getSiteId();
    }

    /**
     * getSiteId
     *
     * @return int|null
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * Set Page Revision
     *
     * @param Revision $revision Revision object to add
     *
     * @return void
     */
    public function addRevision(Revision $revision)
    {
        $this->revisions->set($revision->getRevisionId(), $revision);
    }

    /**
     * Get the entire revision list
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getRevisions()
    {
        return $this->revisions;
    }

    /**
     * Overwrite revisions and Set a group of revisions
     *
     * @param array $revisions Array of Revisions to be added
     *
     * @throws InvalidArgumentException
     */
    public function setRevisions(array $revisions)
    {
        $this->revisions = new ArrayCollection();

        /** @var \Rcm\Entity\Revision $revision */
        foreach ($revisions as $revision) {
            if (!$revision instanceof Revision) {
                throw new InvalidArgumentException(
                    "Invalid Revision passed in.  Unable to set array"
                );
            }

            $this->revisions->set($revision->getRevisionId(), $revision);
        }
    }

    /**
     * Return the last draft saved.
     *
     * @return Revision
     */
    public function getLastSavedDraftRevision()
    {
        if (!empty($this->lastSavedDraft)) {
            return $this->lastSavedDraft;
        }

        $published = $this->publishedRevision;
        $staged = $this->stagedRevision;

        $arrayCollection = $this->revisions->toArray();

        /** @var \Rcm\Entity\Revision $revision */
        $revision = end($arrayCollection);

        if (empty($revision)) {
            return null;
        }

        $found = false;

        while (!$found) {
            if (empty($revision)) {
                break;
            } elseif (!empty($published)
                && $published->getRevisionId() == $revision->getRevisionId()
            ) {
                $found = false;
            } elseif (!empty($staged)
                && $staged->getRevisionId() == $revision->getRevisionId()
            ) {
                $found = false;
            } elseif ($revision->wasPublished()) {
                $found = false;
            } else {
                $found = true;
            }

            if (!$found) {
                $revision = prev($arrayCollection);
            }
        }

        return $this->lastSavedDraft = $revision;
    }

    /**
     * Get a page revision by ID
     *
     * @param int $revisionId
     *
     * @return null|Revision
     */
    public function getRevisionById($revisionId)
    {
        return $this->revisions->get($revisionId);
    }

    /**
     * getCurrentRevision
     *
     * @return Revision
     */
    public function getCurrentRevision()
    {
        return $this->currentRevision;
    }

    /**
     * setCurrentRevision
     *
     * @param $currentRevision
     *
     * @return void
     */
    public function setCurrentRevision($currentRevision)
    {
        $this->currentRevision = $currentRevision;
    }

    /**
     * populate
     *
     * @param array $data
     * @param array $ignore List of properties to skip population for
     *
     * @return void
     */
    public function populate(
        array $data,
        array $ignore = ['createdByUserId', 'createdDate', 'createdReason']
    ) {
        $prefix = 'set';

        foreach ($data as $property => $value) {
            // Check for ignore keys
            if (in_array($property, $ignore)) {
                continue;
            }

            $method = $prefix . ucfirst($property);

            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    /**
     * populateFromObject
     *
     * @param ApiPopulatableInterface $object Object of THIS type
     * @param array                   $ignore List of properties to skip population for
     *
     * @return void
     */
    public function populateFromObject(
        ApiPopulatableInterface $object,
        array $ignore = ['createdByUserId', 'createdDate', 'createdReason']
    ) {
        if ($object instanceof ContainerInterface) {
            $this->populate($object->toArray(), $ignore);
        }
    }

    /**
     * jsonSerialize
     *
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->toArray(['revisions', 'site']);
    }

    /**
     * toArray
     *
     * @param array $ignore
     *
     * @return mixed
     */
    public function toArray($ignore = ['revisions', 'site'])
    {
        $prefix = 'get';
        $properties = get_object_vars($this);
        $data = [];

        foreach ($properties as $property => $value) {
            // Check for ignore keys
            if (in_array($property, $ignore)) {
                continue;
            }

            $method = $prefix . ucfirst($property);

            if (method_exists($this, $method)) {
                $data[$property] = $this->$method();
            }
        }

        if (!in_array('revisions', $ignore)) {
            $data['revisions'] = $this->modelArrayToArray(
                $this->getRevisions()->toArray(),
                []
            );
        }

        if (!in_array('siteId', $ignore)) {
            $data['siteId'] = $this->getSiteId();
        }

        if (!in_array('createdDateString', $ignore)) {
            $data['createdDateString'] = $this->getCreatedDateString();
        }

        if (!in_array('lastPublishedString', $ignore)) {
            $data['lastPublishedString'] = $this->getLastPublishedString();
        }

        return $data;
    }

    /**
     * modelArrayToArray
     *
     * @param array $modelArray
     * @param array $ignore
     *
     * @return array
     */
    protected function modelArrayToArray($modelArray, $ignore = [])
    {
        $array = [];

        /** @var ApiSerializableInterface $item */
        foreach ($modelArray as $item) {
            $array[] = $item->toArray($ignore);
        }

        return $array;
    }
}
