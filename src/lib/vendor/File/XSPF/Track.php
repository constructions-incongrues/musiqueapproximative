<?php
/**
 * +---------------------------------------------------------------------------+
 * | File_XSPF PEAR Package for Manipulating XSPF Playlists                    |
 * | Copyright (c) 2005 David Grant <david@grant.org.uk>                       |
 * +---------------------------------------------------------------------------+
 * | This library is free software; you can redistribute it and/or             |
 * | modify it under the terms of the GNU Lesser General Public                |
 * | License as published by the Free Software Foundation; either              |
 * | version 2.1 of the License, or (at your option) any later version.        |
 * |                                                                           |
 * | This library is distributed in the hope that it will be useful,           |
 * | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
 * | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU         |
 * | Lesser General Public License for more details.                           |
 * |                                                                           |
 * | You should have received a copy of the GNU Lesser General Public          |
 * | License along with this library; if not, write to the Free Software       |
 * | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA |
 * +---------------------------------------------------------------------------+
 *
 * PHP version 5
 *
 * @category  File
 * @package   File_XSPF
 * @author    David Grant <david@grant.org.uk>
 * @copyright 2005 David Grant
 * @license   http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @version   CVS: $Id$
 * @link      http://www.xspf.org/
 */

/**
 * This class is the objectification of an XSPF Track element.
 *
 * @category File
 * @package  File_XSPF
 * @author   David Grant <david@grant.org.uk>
 * @license  LGPL <http://www.gnu.org/licenses/lgpl.html>
 * @link     http://pear.php.net/package/File_XSPF
 */
class File_XSPF_Track
{
    /**
     * Human-readable name of the collection from which this track came.
     *
     * @access  private
     * @var     string
     */
    var $_album;

    /**
     * The human-readable comment of this track.
     *
     * @access  private
     * @var     string
     */
    var $_annotation;

    /**
     * Number of album elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_album = 0;

    /**
     * Number of annotation elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_annotation = 0;

    /**
     * Number of creator elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_creator = 0;

    /**
     * Number of duration elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_duration = 0;

    /**
     * Number of image elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_image = 0;

    /**
     * Number of info elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_info = 0;

    /**
     * Number of title elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_title = 0;

    /**
     * Number of tracknum elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_tracknum = 0;

    /**
     * The human-readable name of this track's creator, e.g. 'Radiohead'.
     *
     * @access  private
     * @var     string
     */
    var $_creator;

    /**
     * The duration of this track in milliseconds.
     *
     * @access  private
     * @var     int
     */
    var $_duration = 0;

    /**
     * An array of File_XSPF_Extension instances.
     *
     * @access  private
     * @var     array
     */
    var $_extensions = array();

    /**
     * Canonical URN for this track.
     *
     * @access  private
     * @var     string
     */
    var $_identifier;

    /**
     * URL of an image to display for the duration of this track.
     *
     * @access  private
     * @var     string
     */
    var $_image;

    /**
     * URL of a place where this resource can be bought or more info may be found.
     *
     * @access  private
     * @var     string
     */
    var $_info;

    /**
     * An array of File_XSPF_Link instances.
     *
     * @access  private
     * @var     array
     */
    var $_links = array();

    /**
     * A multi-dimensional array of various location elements.
     *
     * @access  private
     * @var     array
     */
    var $_locations = array();

    /**
     * An array of File_XSPF_Meta instances.
     *
     * @access  private
     * @var     array
     */
    var $_meta = array();

    /**
     * The human-readable name of this track, e.g. 'Planet Telex'
     *
     * @access  private
     * @var     string
     */
    var $_title;

    /**
     * Ordinal position of this track on the collection described in $_album.
     *
     * @access  private
     * @var     int
     */
    var $_trackNum;

    /**
     * Add an extension element to this track.
     *
     * This method adds an extension element to this track.  This function will
     * only accept instances of the File_XSPF_Extension class, which is documented
     * elsewhere.
     *
     * @param File_XSPF_Extension $extension an instance of File_XSPF_Extension
     *
     * @access public
     * @return void
     */
    function addExtension($extension)
    {
        if (is_object($extension) && is_a($extension, 'file_xspf_extension')) {
            $this->_extensions[] = $extension;
        }
    }

    /**
     * Add a link element to this track.
     *
     * This method adds a link element to this track.  The $link parameter must be a
     * instance of the File_XSPF_Link class or the method will fail.
     *
     * @param File_XSPF_Link $link an instance of the File_XSPF_Link class.
     *
     * @access public
     * @return void
     */
    function addLink($link)
    {
        if (is_object($link) && is_a($link, 'file_xspf_link')) {
            $this->_links[] = $link;
        }
    }

    /**
     * Add a location element to this track.
     *
     * This method adds the data of a location element to this track.  This is
     * the URL of this track, so probably an audio resource.  Local files to be
     * added should be prepended with a file:// scheme.
     *
     * @param File_XSPF_Location $location the URL of a resource for rendering.
     * @param boolean            $append   true to append the location, or false
     *                                     to prepend it.
     *
     * @access public
     * @return bool
     */
    function addLocation($location, $append = true)
    {
        if (File_XSPF::_validateURL($location->_url)) {
            if ($append) {
                array_push($this->_locations, $location);
            } else {
                array_unshift($this->_locations, $location);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add a meta element to this track.
     *
     * This method adds a meta element to this track.  The $meta parameter must be an
     * instance of the File_XSPF_Meta class or the method will fail.
     *
     * @param File_XSPF_Meta $meta an instance of the File_XSPF_Meta class.
     *
     * @access public
     * @return void
     */
    function addMeta($meta)
    {
        if (is_object($meta) && is_a($meta, 'file_xspf_meta')) {
            $this->_meta[] = $meta;
        }
    }

    /**
     * Get the name of the album for this track.
     *
     * This method returns the name of the collection from which this track
     * comes from.  For example, 'OK Computer'.
     *
     * @access  public
     * @return  string the name of the album for this track.
     */
    function getAlbum()
    {
        return $this->_album;
    }

    /**
     * Get the human-readable description of this track.
     *
     * This method returns the human-readable description of this track, which
     * might be notes accompanying this track.
     *
     * @access  public
     * @return  string a description of this track.
     */
    function getAnnotation()
    {
        return $this->_annotation;
    }

    /**
     * Get an array of locations for this track.
     *
     * This method returns an array of URLs for the rendering of this track.
     * These URLs will most likely be audio tracks for indepretation by a
     * media player.
     *
     * @access  public
     * @return  array an array of URLs.
     */
    function getLocation()
    {
        return $this->_locations;
    }

    /**
     * Get the canonical ID for this track.
     *
     * This method returns the URN used to correctly identify a resource, which
     * might be a hash, a MusicBrainz identifier, or an ISRC.
     *
     * @access  public
     * @return  File_XSPF_Identifier a URN to identify this track.
     */
    function getIdentifier()
    {
        return $this->_identifier;
    }

    /**
     * Get the title of this track.
     *
     * This method returns the title of this track, which is the human-readable
     * name of the recording, such as 'Planet Telex'.
     *
     * @access  public
     * @return  string
     */
    function getTitle()
    {
        return $this->_title;
    }

    /**
     * Set the identifier object for this track.
     *
     * This method sets the identifier for this track, which must be an instance
     * of the File_XSPF_Identifier class.
     *
     * @param File_XSPF_Identifier $identifier an instance of File_XSPF_Identifier
     *
     * @access public
     * @return void
     */
    function setIdentifier($identifier)
    {
        $this->_identifier = $identifier;
    }

    /**
     * Set the human-readable title of this track.
     *
     * This method sets the human-readable title of this track, which is the
     * name by which it is most-often referred to, such as 'Planet Telex'.
     *
     * @param string $title the human-readable title of this track.
     *
     * @access public
     * @return void
     */
    function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * Get the human-readable name of the track author.
     *
     * This method returns the human-readable name of the entity responsible
     * for this track, which is most commonly the artist, but might be a
     * conductor or arranger.
     *
     * @access  public
     * @return  string  the human-readable name of the entity responsible 
     *                  for this track.
     */
    function getCreator()
    {
        return $this->_creator;
    }

    /**
     * Set the human-readable name of the track author.
     *
     * This method sets the human-readable name of the track author, which might be
     * the original artist, composer, or arranger.  For example, 'Radiohead'.
     *
     * @param string $creator the human-readable name of the entity responsible 
     *                        for this track.
     *
     * @access public
     * @return void
     */
    function setCreator($creator)
    {
        $this->_creator = $creator;
    }

    /**
     * Set a human-readable comment on this track.
     *
     * This method sets a human-readable description of this track, which may contain
     * listening notes, or a review of the track.
     *
     * @param string $annotation a human-readable comment on this track.
     *
     * @access public
     * @return boolean
     */
    function setAnnotation($annotation)
    {
        if (strcmp($annotation, strip_tags($annotation)) == 0) {
            $this->_annotation = $annotation;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the URL of a place where this track may be bought.
     *
     * This method returns the URL of a place, such as an online shop, where this
     * track may be bought, or somewhere where more information about the track
     * can be found, such as a musical encyclopaedia.
     *
     * @access  public
     * @return  string the URL of a place for buying this track, or 
     *          finding out more about it.
     */
    function getInfo()
    {
        return $this->_info;
    }

    /**
     * Set the URL of where to buy this track.
     *
     * This method sets the URL of a place where this track may be bought, or 
     * somewhere where more information about the track can be found.  
     * An example might be a page on Amazon.com, or iTunes.
     *
     * @param string $info a URL where this track may be bought.
     *
     * @access public
     * @return void
     */
    function setInfo($info)
    {
        if (File_XSPF::_validateURL($info)) {
            $this->_info = $info;
            return true;
        } else {
            return false;
        }
    }


    /**
     * Get an array of File_XSPF_Meta instances.
     *
     * This method returns an array of File_XSPF_Meta instances used to define
     * metadata resources for this track.
     *
     * @access  public
     * @return  array    an array of File_XSPF_Meta instances.
     */
    function getMeta()
    {
        return $this->_meta;
    }

    /**
     * Get an array of File_XSPF_Link instances.
     *
     * This method returns an array of File_XSPF_Link instances used to define
     * non-XSPF data relevant to this track.
     *
     * @access  public
     * @return  array an array of File_XSPF_Link instances.
     */
    function getLink()
    {
        return $this->_links;
    }

    /**
     * Get the image to display for this track.
     *
     * This method returns the URL of the image resource to be displayed for the
     * duration of this track.  If this image does not exist, clients should
     * fall back to the image specified for the playlist.
     *
     * @access  public
     * @return  string an image resource URL.
     */
    function getImage()
    {
        return $this->_image;
    }

    /**
     * Set the URL of the image to display for the duration of this track.
     *
     * This method sets the URL of the image that a content aggregator should
     * display for the duration of this track being played.  If this is not set,
     * clients should fall back to the image specified for this playlist.
     *
     * @param string $image an image resource URL.
     *
     * @access public
     * @return bool
     */
    function setImage($image)
    {
        if (File_XSPF::_validateURL($image)) {
            $this->_image = $image;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the name of the album that contains this track.
     *
     * This method sets the name of an album that contains this track, if indeed
     * this track came from a collection.
     *
     * @param string $album the collection on which this track appears.
     *
     * @access public
     * @return void
     */
    function setAlbum($album)
    {
        $this->_album = $album;
    }

    /**
     * Get the track number of this track.
     *
     * This method returns the number representing the offset of this track
     * within a collection of tracks, such as an album.
     *
     * @access  public
     * @return  int the ordinal position of the track on its collection.
     */
    function getTrackNumber()
    {
        return $this->_trackNum;
    }

    /**
     * Set the number of this track.
     *
     * This method sets the track number for this track.  If this track is part
     * of a larger collection, such as an album, this will be the offset at
     * which the track appears on the collection.
     *
     * @param int $trackNum the ordinal position of the track on its collection.
     *
     * @access  public
     * @return  boolean
     */
    function setTrackNumber($trackNum)
    {
        if (ctype_digit($trackNum) && $trackNum >= 0) {
            $this->_trackNum = intval($trackNum);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the duration of this track in milliseconds.
     *
     * This method returns the duration of this track in milliseconds, and not
     * seconds, as might be expected.
     *
     * @access  public
     * @return  int  the duration of this track in milliseconds.
     */
    function getDuration()
    {
        return $this->_duration;
    }

    /**
     * Set the duration of this track.
     *
     * This method sets the duration of this track in milliseconds, and not seconds.
     * This method will use the {@link http://php.net/intval intval} method to cast
     * the supplied duration to an integer.
     *
     * @param int $duration the length of this track in milliseconds.
     *
     * @access  public
     * @return  boolean
     */
    function setDuration($duration)
    {
        if (ctype_digit($duration) && $duration >= 0) {
            $this->_duration = intval($duration);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Append this object to the parent xml node.
     *
     * This method adds this object to the passed XML parent node, which is an
     * instance of XML_Tree_Node.
     *
     * @param XML_Tree_Node $parent Parent node
     *
     * @access private
     * @return void
     */
    function _toXml($parent)
    {
        $track = $parent->addChild('track');
        if ($this->_album) {
            $track->addChild('album', $this->getAlbum());
        }
        if ($this->_annotation) {
             $track->addChild('annotation', $this->getAnnotation());
        }
        if ($this->_creator) {
            $track->addChild('creator', $this->getCreator());
        }
        if ($this->_duration) {
            $track->addChild('duration', $this->getDuration());
        }
        if (count($this->_extensions)) {
            foreach ($this->_extensions as $extension) {
                $extension->_toXml($track);
            }
        }
        if ($this->_identifier) {
            $track->addChild('identifier', $this->getIdentifier());
        }
        if ($this->_image) {
            $track->addChild('image', $this->getImage());
        }
        if ($this->_info) {
            $track->addChild('info', $this->getInfo());
        }
        if (count($this->_links)) {
            foreach ($this->_links as $link) {
                $link->_toXml($track);
            }
        }
        if (count($this->_locations)) {
            foreach ($this->_locations as $location) {
                $location->_toXml($track);
            }
        }
        if (count($this->_meta)) {
            foreach ($this->_meta as $meta) {
                $meta->_toXml($track);
            }
        }
        if ($this->_title) {
            $track->addChild('title', $this->getTitle());
        }
        if ($this->_trackNum) {
            $track->addChild('trackNum', $this->getTrackNumber());
        }
    }
}
?>
