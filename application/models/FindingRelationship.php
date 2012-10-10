<?php

/**
 * FindingRelationship
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6401 2009-09-24 16:12:04Z guilhermeblanco $
 */
class FindingRelationship extends BaseFindingRelationship
{
    public function getReverseAction()
    {
        $tags = explode(',', Fisma::configuration()->getConfig('finding_link_types'));
        foreach ($tags as $tag) {
            if (strpos($tag, $this->relationship) !== false) {
                $components = explode('/', $tag);
                if (count($components) > 1) { // if there are 2 parts
                    return $components[1]; // returns the second
                } else {
                    return $tag; // otherwise returns itself
                }
            }
        }
        return $this->relationship; // $relationship outdated, returns itself
    }

    public function getDirectAction()
    {
        $tags = explode(',', Fisma::configuration()->getConfig('finding_link_types'));
        foreach ($tags as $tag) {
            if (strpos($tag, $this->relationship) !== false) {
                $components = explode('/', $tag);
                if (count($components) > 1) { // if there are 2 parts
                    return $components[0]; // returns the first
                } else {
                    return $tag; // otherwise returns itself
                }
            }
        }
        return $this->relationship; // $relationship outdated, returns itself
    }

    public static function isDirectAction($relationship)
    {
        $tags = explode(',', Fisma::configuration()->getConfig('finding_link_types'));
        foreach ($tags as $tag) {
            if (strpos($tag, $relationship) !== false) {
                $components = explode('/', $tag);
                if (count($components) > 1) { // if there are 2 parts
                    return (strpos($tag, $relationship) === 0); // returns whether it's the first
                } else {
                    return $tag; // otherwise returns true
                }
            }
        }
        return true; // $relationship outdated, returns true
    }

    public static function getFullTag($relationship)
    {
        $tags = explode(',', Fisma::configuration()->getConfig('finding_link_types'));
        foreach ($tags as $tag) {
            if (strpos($tag, $relationship) !== false) {
                return $tag;
            }
        }
        return $relationship; // $relationship outdated, returns itself
    }
}