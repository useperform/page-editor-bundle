<?php

namespace Perform\PageEditorBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Perform\PageEditorBundle\Entity\Version;

/**
 * VersionRepository
 *
 * @author Glynn Forrest <me@glynnforrest.com>
 **/
class VersionRepository extends EntityRepository
{
    /**
     * Mark a version as published, marking all other versions for that page as
     * unpublished.
     */
    public function markPublished(Version $version)
    {
        $query = $this->_em->createQuery(
            'UPDATE PerformPageEditorBundle:Version v SET v.published = false WHERE v.page = :page'
        );
        $query->setParameter('page', $version->getPage());
        $query->execute();

        $version->setPublished(true);
        $this->_em->persist($version);
        $this->_em->flush();
    }

    /**
     * Get the names of pages with at least one version.
     *
     * @return array
     */
    public function getPageNames()
    {
        $query = $this->_em->createQuery(
            'SELECT DISTINCT v.page FROM PerformPageEditorBundle:Version v ORDER BY v.page ASC'
        );

        return array_map(function($result) {
            return $result['page'];
        }, $query->getScalarResult());
    }

    /**
     * Find the default version for a page - either the published version, or
     * the most recent version if no version has been published yet. A new
     * version will be created if none exist.
     */
    public function findDefaultVersion($page)
    {
        $published = $this->findOneBy(['published' => true, 'page' => $page]);
        if ($published) {
            return $published;
        }

        $mostRecent = $this->findOneBy(['page' => $page], ['updatedAt' => 'DESC']);
        if ($mostRecent) {
            return $mostRecent;
        }

        $now = new \DateTime();
        $firstVersion = new Version();
        $firstVersion->setPage($page)
            ->setTitle('Untitled '.$now->format('Y/m/d h:i:s'));
        $this->_em->persist($firstVersion);
        $this->_em->flush();

        return $firstVersion;
    }

    /**
     * Find versions for the same page as the given version.
     */
    public function findRelated(Version $version)
    {
        return $this->findBy([
            'page' => $version->getPage(),
        ], [
            'updatedAt' => 'DESC',
        ]);
    }
}
