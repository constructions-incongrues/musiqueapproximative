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

require_once 'File/XSPF/Exception.php';
require_once 'File/XSPF/Extension.php';
require_once 'File/XSPF/Handler.php';
require_once 'File/XSPF/Identifier.php';
require_once 'File/XSPF/Link.php';
require_once 'File/XSPF/Location.php';
require_once 'File/XSPF/Meta.php';
require_once 'File/XSPF/Track.php';

require_once 'Validate.php';

require_once 'XML/Parser.php';
require_once 'XML/Tree.php';

/**
 * This is the main class for this package.
 *
 * This class serves as the central point for all other classes in this
 * package, and provides the majority of manipulative methods for outputting
 * the XSPF playlist.
 *
 * @example  examples/example_1.php  Generating a One Track Playlist
 * @example  examples/example_2.php  Filtering an Existing Playlist
 * @example  examples/example_3.php  Cataloging a Music Collection
 * @example  examples/example_4.php  Retrieving Statistics from Audioscrobbler
 * @category File
 * @package  File_XSPF
 * @author   David Grant <david@grant.org.uk>
 * @license  LGPL <http://www.gnu.org/licenses/lgpl.html>
 * @link     http://pear.php.net/package/File_XSPF
 */
class File_XSPF
{

    /**
     * Constant to identify an attribution as a location element.
     *
     * This constant may be passed as the second argument to the
     * {@link File_XSPF::addAttribution()} method of this class to
     * signify that the passed data is a location element.
     *
     * @link    File_XSPF::addAttribution()
     */
    const ATTRIBUTION_LOCATION = 1;
    /**
     * Constant to identify an attribution as an identifier element.
     *
     * This constant may be passed as the second argument to the
     * {@link File_XSPF::addAttribution()} method of this class to
     * signify that the passed data is an identifier element.
     *
     * @link    File_XSPF::addAttribution()
     */
    const ATTRIBUTION_IDENTIFIER = 2;

    /**
     * This constant signifies an error closing a file.
     */
    const ERROR_FILE_CLOSURE = 1;
    /**
     * This constant signifies an error opening a file.
     */
    const ERROR_FILE_OPENING = 2;
    /**
     * This constant signfies an error writing to a file.
     */
    const ERROR_FILE_WRITING = 3;
    /**
     * This constant signifies an error parsing the XSPF file.
     */
    const ERROR_PARSING_FAILURE = 4;

    /**
     * A human-readable comment on this playlist.
     *
     * @access  private
     * @var     string
     */
    var $_annotation;

    /**
     * A multi-dimensional array of location and identifier elements.
     *
     * @access  private
     * @var     array
     */
    var $_attributions = array();

    /**
     * Number of annotation elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_annotation = 0;

    /**
     * Number of attribution elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_attribution = 0;

    /**
     * Number of creator elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_creator = 0;

    /**
     * Number of date elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_date = 0;

    /**
     * Number of identifier elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_identifier = 0;

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
     * Number of license elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_license = 0;

    /**
     * Number of location elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_location = 0;

    /**
     * Number of title elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_title = 0;

    /**
     * Number of tracklist elements found in file parsing session.
     *
     * @access  private
     * @var     int
     */
    var $_count_tracklist = 0;

    /**
     * Human-readable name of the entity responsible for this playlist.
     *
     * @access  private
     * @var     string
     */
    var $_creator;

    /**
     * Creation date of this playlist in XML schema dateTime format.
     *
     * @access  private
     * @var     string
     */
    var $_date;

    /**
     * An array of File_XSPF_Extension instances.
     *
     * @access  private
     * @var     array
     */
    var $_extensions = array();

    /**
     * Canonical ID for this playlist as a URN.
     *
     * @access  private
     * @var     string
     */
    var $_identifier;

    /**
     * The URL of an image to display in default of a track image.
     *
     * @access  private
     * @var     string
     */
    var $_image;

    /**
     * The URL of a web page to find out more about this playlist.
     *
     * @access  private
     * @var     string
     */
    var $_info;

    /**
     * The URL of the license for this playlist.
     *
     * @access  private
     * @var     string
     */
    var $_license;

    /**
     * An array of File_XSPF_Link instances.
     *
     * @access  private
     * @var     array
     */
    var $_links = array();

    /**
     * The source URL of this playlist.
     *
     * @access  private
     * @var     string
     */
    var $_location;

    /**
     * An array of File_XSPF_Meta instances.
     *
     * @access  private
     * @var     array
     */
    var $_meta = array();

    /**
     * An array of File_XSPF_Track instances.
     *
     * @access  private
     * @var     array
     */
    var $_tracks = array();

    /**
     * The human-readable title of this playlist.
     *
     * @access  private
     * @var     string
     */
    var $_title;

    /**
     * The version of XSPF specification being used.
     *
     * @access  private
     * @var     int
     */
    var $_version = 1;

    /**
     * The namespace definition for this format.
     *
     * @access  private
     * @var     string
     */
    var $_xmlns = "http://xspf.org/ns/0/";

    /**
     * Enter description here...
     *
     * @var     boolean
     */
    var $_parse_error = false;

    /**
     * Creates a new File_XSPF object.
     *
     * @access  public
     * @return  File_XSPF
     */
    function File_XSPF()
    {
    }

    /**
     * Parses an existing XSPF file.
     *
     * This method parses an existing XSPF file into the current File_XSPF instance.
     * If successful, this function returns true, otherwise it will return an
     * instance of PEAR_Error.
     *
     * @param string $path Path to file
     *
     * @access  public
     * @return  bool|PEAR_Error
     */
    function parseFile($path)
    {
        $parser = new XML_Parser();
        $handle = new File_XSPF_Handler($this);

        $parser->setInputFile($path);

        $parser->setHandlerObj($handle);
        $parser->parse();

        return true;
    }

    /**
     * Parses an XSPF text stream.
     *
     * This method parses an XSPF text stream into the current File_XSPF instance.
     * If successful, this function returns true, otherwise it will return an
     * instance of PEAR_Error.
     *
     * @param string $text Text stream
     *
     * @access  public
     * @return  mixed
     */
    function parse($text)
    {
        $parser = new XML_Parser();
        $handle = new File_XSPF_Handler($this);

        $result = $parser->setInputString($text);
        $parser->setHandlerObj($handle);
        $result = $parser->parse();
        return true;
    }


    /**
     * Add an identifier or location tag to the playlist attribution.
     *
     * This method adds a identifier or location tag to the playlist
     * attribution.  The first parameter must be an instance of either the
     * File_XSPF_Identifier or File_XSPF_Location classes.
     *
     * The third parameter, $append, affects the output of the order of the
     * children of the attribution element.  According to the specification, the
     * children of the attribution element should be in chronological order, so
     * this parameter is included to make the job somewhat more simplistic.
     *
     * @param object  $attribution File_XSPF_Identifier|File_XSPF_Location
     * @param boolean $append      true to append, or false to prepend.
     *
     * @access  public
     * @see     File_XSPF::getLicense()
     * @return  void
     */
    function addAttribution($attribution, $append = true)
    {
        if ($append) {
            array_push($this->_attributions, $attribution);
        } else {
            array_unshift($this->_attributions, $attribution);
        }
    }

    /**
     * Add an extension element to the playlist.
     *
     * This method adds an extension element to the playlist.  This function
     * will only accept instances of the File_XSPF_Extension class, which is
     * documented elsewhere.
     *
     * @param File_XSPF_Extension $extension an instance of File_XSPF_Extension
     *
     * @access  public
     * @return  void
     */
    function addExtension($extension)
    {
        if (is_object($extension) && is_a($extension, "file_xspf_extension")) {
            $this->_extensions[] = $extension;
        }
    }

    /**
     * Add a link element to the playlist.
     *
     * This method adds a link element to the playlist.  The $link parameter
     * must be a instance of the {@link File_XSPF_Link File_XSPF_Link} class or
     * the method will fail.
     *
     * @param File_XSPF_Link $link an instance of File_XSPF_Link
     *
     * @access  public
     * @return  void
     */
    function addLink($link)
    {
        if (is_object($link) && is_a($link, "file_xspf_link")) {
            $this->_links[] = $link;
        }
    }

    /**
     * Add a meta element to the playlist.
     *
     * This method adds a meta element to the playlist.  The $meta parameter
     * must be an instance of the {@link File_XSPF_Meta File_XSPF_Meta} class or
     * the method will fail.
     *
     * @param File_XSPF_Meta $meta an instance of File_XSPF_Meta.
     *
     * @access  public
     * @return  void
     */
    function addMeta($meta)
    {
        if (is_object($meta) && is_a($meta, "file_xspf_meta")) {
            $this->_meta[] = $meta;
        }
    }

    /**
     * Add a track element to the playlist.
     *
     * This method adds a track element to the playlist.  Complimentary
     * documentation exists for the {@link File_XSPF_Track File_XSPF_Track}
     * class, and should be the focus of the majority of attention for users
     * building a XSPF playlist.
     *
     * @param File_XSPF_Track $track an instance of File_XSPF_Track.
     *
     * @access  public
     * @return  void
     */
    function addTrack($track)
    {
        if (is_object($track) && is_a($track, "file_xspf_track")) {
            $this->_tracks[] = $track;
        }
    }

    /**
     * Get the annotation for this playlist.
     *
     * This method returns the contents of the annotation element, which
     * is the human-readable comment of this playlist.
     *
     * @access  public
     * @return  string the annotation data for this playlist.
     */
    function getAnnotation()
    {
        return $this->_annotation;
    }

    /**
     * Get an array of attribution elements.
     *
     * This method returns an array of attribution elements.
     *
     * @param int $offset the offset of the attribution to retrieve.
     *
     * @access  public
     * @return  File_XSPF_Identifier|File_XSPF_Location
     *
     * @see     File_XSPF::getLicense()
     */
    function getAttribution($offset = 0)
    {
        if (isset($this->attributions[$offset])) {
            return $this->_attributions[$offset];
        }
    }

    /**
     * Get an array of attribution elements.
     *
     * This method returns a list of attribution elements, which is either an
     * instance of File_XSPF_Identifier or File_XSPF_Location.
     *
     * @param unknown $filter Undocumented
     *
     * @access  public
     * @return  array
     */
    function getAttributions($filter = null)
    {
        if (is_null($filter)) {
            return $this->_attributions;
        }

        $attributions = array();
        foreach ($this->_attributions as $attribution) {
            $is_identifier = $filter & File_XSPF::ATTRIBUTION_IDENTIFIER;
            $is_location   = $filter & File_XSPF::ATTRIBUTION_LOCATION;

            if ($is_identifier && is_a($attribution, 'file_xspf_identifier')) {
                $attributions[] = $attribution;
            } elseif ($is_location && is_a($attribution, 'file_xspf_location')) {
                $attributions[] = $attribution;
            }
        }
        return $attributions;
    }

    /**
     * Get the author of this playlist.
     *
     * This method returns the contents of the creator element, which
     * represents the author of this playlist.
     *
     * @access  public
     * @return  string  the creator of this playlist as a human-readable string.
     */
    function getCreator()
    {
        return $this->_creator;
    }

    /**
     * Get the date of creation for this playlist.
     *
     * This method returns the date on which this playlist was created (not
     * last modified), formatted as a XML schema dateTime, which is the same as
     * the 'r' parameter for {@link http://php.net/date date()} in PHP5.
     *
     * @access  public
     * @return  string  a XML schema dateTime formatted date.
     */
    function getDate()
    {
        return $this->_date;
    }

    /**
     * Get the duration of this playlist in seconds.
     *
     * This method returns the length of this playlist in seconds.  These times
     * are taken from the duration elements of the playlist track elements.
     *
     * @access  public
     * @return  int the length in seconds of this playlist.
     */
    function getDuration()
    {
        $duration = 0;
        foreach ($this->_tracks as $track) {
            $duration += $track->getDuration();
        }
        return (floor($duration / 1000));
    }

    /**
     * Get an identifier for this playlist.
     *
     * This method returns a canonical ID for this playlist as a URN.  An
     * example might be an SHA1 hash of the tracklisting, e.g.
     * sha1://0beec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33
     *
     * @access  public
     * @return  string a valid URN for identifing this playlist.
     */
    function getIdentifier()
    {
        return $this->_identifier;
    }

    /**
     * Get the image URL for this playlist.
     *
     * This method returns the URL of the image used to represent this
     * playlist.  This image should be used if individual tracks belonging
     * to this playlist do not have their own image.
     *
     * @access  public
     * @return  string the URL of the image for this playlist.
     */
    function getImage()
    {
        return $this->_image;
    }

    /**
     * Get the URL of a web page containing information about this playlist.
     *
     * This method returns the URL of a web page, allowing the user to find
     * out more information about the author of the playlist, and find other
     * playlists.
     *
     * @access  public
     * @return  string a URL containing information about this playlist.
     */
    function getInfo()
    {
        return $this->_info;
    }

    /**
     * Get the license for this playlist.
     *
     * This method returns the URL of the license under which this playlist has
     * been or will be released, such as http://www.gnu.org/copyleft/lesser.html
     * for the LGPL.  If the specified license contains a requirement for
     * attribution, users should use the
     * {@link File_XSPF::getAttribution() getAttribution} method to retrieve an
     * array of attributions.
     *
     * @access  public
     * @link    File_XSPF::getAttribution()
     * @return  string  the URL of the license for this playlist.
     */
    function getLicense()
    {
        return $this->_license;
    }

    /**
     * Get an array of link elements for this playlist.
     *
     * This method returns a list of link elements, which contain non-XSPF web
     * resources, which still relate to this playlist.
     *
     * @access  public
     * @return  array   an array of File_XSPF_Link instances.
     * @see     File_XSPF::getMeta()
     */
    function getLink()
    {
        return $this->_links;
    }

    /**
     * Get the source URL for this playlist.
     *
     * This methods returns the URL where this playlist may be found, such as
     * the path to an FTP or HTTP server, or perhaps the path to a file on
     * the users local machine.
     *
     * @access  public
     * @return  string  the URL where this playlist may be found.
     */
    function getLocation()
    {
        return $this->_location;
    }

    /**
     * Get an array of non-XSPF metadata.
     *
     * This method returns an array of meta elements associated with this
     * playlist.  Meta elements contain metadata not covered by the XSPF
     * specification without breaking XSPF validation.
     *
     * @access  public
     * @return  array   an array of File_XSPF_Meta instances.
     * @see     File_XSPF::getLink()
     */
    function getMeta()
    {
        return $this->_meta;
    }

    /**
     * Get the human-readable title of this playlist.
     *
     * This method returns the human-readable title of this playlist, which may
     * be a simple reference to what the playlist contains, e.g. "Favourites".
     *
     * @access  public
     * @return  string  the human-readable title of this playlist.
     */
    function getTitle()
    {
        return $this->_title;
    }

    /**
     * Get an array of tracks for this playlist.
     *
     * This method returns an array of {@link File_XSPF_Track File_XSPF_Track}
     * objects belonging to this playlist, which directly represent individual
     * tracks on this playlist.
     *
     * @access  public
     * @return  array   an array of File_XSPF_Track instances.
     * @see     File_XSPF_Track
     */
    function getTracks()
    {
        return $this->_tracks;
    }

    /**
     * Set an annotation for this playlist.
     *
     * This method sets an annotation, or human-readable description of this
     * playlist, e.g. "All the Radiohead tracks in my vast collection."
     *
     * @param string $annotation a human-readable playlist description.
     *
     * @access  public
     * @return  boolean
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
     * Set the creator of this playlist.
     *
     * The method sets the creator element of this playlist, which is the
     * human-readable name of the author of the resource, such as a person's
     * name, or a company, or a group.
     *
     * @param string $creator the name of the creator of this playlist.
     *
     * @access  public
     * @return void
     */
    function setCreator($creator)
    {
        $this->_creator = $creator;
    }

    /**
     * Set the creation date of this playlist.
     *
     * This method sets the creation date (not last-modified date) of this
     * playlist.  If the $date parameter contains only digits, this method will
     * assume it is a timestamp, and format it accordingly.
     *
     * @param mixed $date either an XML schema dateTime or UNIX timestamp.
     *
     * @access  public
     * @return void
     */
    function setDate($date)
    {
        if (ctype_digit($date)) {
            if (version_compare(phpversion(), '5') != -1) {
                $this->_date = date('r', $date);
            } else {
                $this->_date = date('Y-m-d\TH:i:sO', $date);
            }
        } else {
            $this->_date = $date;
        }
    }

    /**
     * Set the identifier for this playlist.
     *
     * This method sets an identifier for this playlist, such as a SHA1 hash
     * of the track listing.  The $identifier must be a valid URN.
     *
     * @param string $identifier the URN of a resource to identify this playlist.
     *
     * @access  public
     * @return  bool
     */
    function setIdentifier($identifier)
    {
        if (File_XSPF::_validateURN($identifier->_uri)) {
            $this->_identifier = $identifier;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the image URL for this playlist.
     *
     * This method sets the image URL for this playlist, which provides a
     * fallback image if individual tracks do not themselves have image URLs
     * set.
     *
     * @param string $image the URL to an image resource.
     *
     * @access  public
     * @return  bool
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
     * Set the URL of web page for this playlist.
     *
     * This method sets the URL of a web page containing information about this
     * playlist, and possibly links to other playlists by the same author.
     *
     * @param string $info the URL of a web page to describe this playlist.
     *
     * @access  public
     * @return  bool
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
     * Set the license for this playlist.
     *
     * This method sets the URL of the license under which this playlist
     * was released.  If the license requires attribution, such as some
     * Creative Commons licenses, such attributions can be added using
     * the {@link File_XSPF::addAttribution() addAttribution} method.
     *
     * @param string $license The URL of the license for this playlist.
     *
     * @access  public
     * @see     File_XSPF::addAttribution()
     * @return  bool
     */
    function setLicense($license)
    {
        if (File_XSPF::_validateURL($license)) {
            $this->_license = $license;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the source URL of this playlist.
     *
     * This method sets the source URL of this playlist.  For example, if
     * one offered one's playlists for syndication over the Internet, one
     * might add a URL to direct users to the original, such as
     * http://www.example.org/list.xspf.
     *
     * @param string $location the source URL of this playlist.
     *
     * @access  public
     * @return  bool
     */
    function setLocation($location)
    {
        if (File_XSPF::_validateURL($location->_url)) {
            $this->_location = $location->_url;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the title of this playlist.
     *
     * This method sets the human-readable title of this playlist.  For example
     * one might call a playlist 'Favourites', or the name of a band.
     *
     * @param string $title the human-readable title of this playlist.
     *
     * @access  public
     * @return  void
     */
    function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * Validate a URI.
     *
     * This method validates a URI against the allowed schemes for this class.
     *
     * @param string $uri a URI to test for validity.
     *
     * @access  private
     * @return  boolean true if valid, false otherwise.
     */
    function _validateUri($uri)
    {
        return File_XSPF::_validateUrl($uri, array('strict' => 'false'))
                    && File_XSPF::_validateUrn($uri);
    }

    /**
     * Validate a URL
     *
     * This method validates a URL, such as http://www.example.org/.
     *
     * @param string $url a URL to test for validity.
     *
     * @access  private
     * @return  boolean true if valid, false otherwise.
     */
    function _validateUrl($url)
    {
        return (Validate::uri($url, array('strict' => ';/?:@$,')));
    }

    /**
     * Validate a URN.
     *
     * This method validates a URN, such as md5://8b1a9953c4611296a827abf8c47804d7
     *
     * @param string $urn a URN to test for validity.
     *
     * @access  private
     * @return  boolean true if valid, false otherwise.
     */
    function _validateUrn($urn)
    {
        //return true;
        return (Validate::uri($urn, array('strict' => false)));
    }

    /**
     * Save this playlist to a file.
     *
     * This method outputs this playlist to a file, or any other location that
     * can be written to by fopen and fwrite.  If the file write is successful,
     * this function will return true, otherwise it will return an instance of a
     * PEAR_Error object.
     *
     * @param string $filename the file to which to write this XSPF playlist.
     *
     * @access  public
     * @return  mixed either true for success, or an instance of PEAR_Error.
     * @throws  PEAR_Error
     */
    function toFile($filename)
    {
        $fp = @fopen($filename, "w");
        if (! $fp) {
            return throw new File_XSPF_Exception("Could Not Open File",
                                    File_XSPF::ERROR_FILE_OPENING);
        }
        if (! fwrite($fp, $this->toString())) {
            return throw new File_XSPF_Exception("Writing to File Failed",
                                    File_XSPF::ERROR_FILE_WRITING);
        }
        if (! fclose($fp)) {
            return throw new File_XSPF_Exception("Failed to Close File",
                                    File_XSPF::ERROR_FILE_CLOSURE);
        }
        return true;
    }

    /**
     * Save this playlist as an M3U playlist.
     *
     * This method saves the current XSPF playlist in M3U format, providing
     * a one-way conversion to the popular flat file playlist.  Reverse conversion
     * is considered to be beyond the scope of this package.
     *
     * @param string $filename the file to which to write the M3U playlist.
     *
     * @access  public
     * @return  mixed either true for success or an instance of PEAR_Error
     * @throws  PEAR_Error
     */
    function toM3U($filename)
    {
        $fp = @fopen($filename, "w");
        if (! $fp) {
            return throw new File_XSPF_Exception("Could Not Open File",
                                    File_XSPF::ERROR_FILE_OPENING);
        }
        foreach ($this->_tracks as $track) {
            $locations = $track->getLocation();
            foreach ($locations as $location) {
                if (! fwrite($fp, $location . "\n")) {
                    return throw new File_XSPF_Exception("Writing to File Failed",
                                            File_XSPF::ERROR_FILE_WRITING);
                }
            }
        }
        if (! fclose($fp)) {
            return throw new File_XSPF_Exception("Failed to Close File",
                                    File_XSPF::ERROR_FILE_CLOSURE);
        }
        return true;
    }

    /**
     * Save this playlist as SMIL format.
     *
     * This method saves this XSPF playlist as a SMIL file, which can be used as a
     * playlist.
     * This is a one-way conversion, as reading SMIL files is considered beyond the
     * scope of this application.
     *
     * @param string $filename the file to which to write the SMIL playlist.
     *
     * @access  public
     *
     * @return  mixed   either true if successful, or an instance of PEAR_Error
     * @throws  PEAR_Error
     */
    function toSMIL($filename)
    {
        $tree = new XML_Tree();
        $root = $tree->addRoot('smil');
        $body = $root->addChild('body');
        $seq  = $body->addChild('seq');

        foreach ($this->_tracks as $track) {
            $locations = $track->getLocation();
            foreach ($locations as $location) {
                if ($tracl->getAnnotation()) {
                    $seq->addChild('audio', '',
                                            array('title' => $track->getAnnotation(),
                                                    'url' => $location));
                } else {
                    $seq->addChild('audio', '', array('url' => $location));
                }
            }
        }

        $fp = @fopen($filename, "w");
        if (!$fp) {
            return throw new File_XSPF_Exception("Could Not Open File",
                                    File_XSPF::ERROR_FILE_OPENING);
        }
        if (!fwrite($fp, $tree->get())) {
            return throw new File_XSPF_Exception("Writing to File Failed",
                                    File_XSPF::ERROR_FILE_WRITING);
        }
        if (!fclose($fp)) {
            return throw new File_XSPF_Exception("Failed to Close File",
                                    File_XSPF::ERROR_FILE_CLOSURE);
        }
        return true;
    }

    /**
     * Output this playlist as a stream.
     *
     * This method outputs this playlist as a HTTP stream with a content type
     * of 'application/xspf+xml', which could be passed off by a user agent to a
     * XSPF-aware application.
     *
     * @access  public
     * @return  void
     */
    function toStream()
    {
        header("Content-type: application/xspf+xml");
        print $this->toString();
    }

    /**
     * Output this playlist as a string.
     *
     * This method outputs this playlist as a string using the XML_Tree package.
     *
     * @access  public
     * @return  string this playlist as a valid XML string.
     */
    function toString()
    {
        $tree = new XML_Tree();
        $root = $tree->addRoot('playlist', '',
                                    array('version' => $this->_version,
                                            'xmlns' => $this->_xmlns));
        if ($this->_annotation) {
            $root->addChild('annotation', $this->getAnnotation());
        }
        if (count($this->_attributions)) {
            $attr = $root->addChild('attribution');
            foreach ($this->_attributions as $attribution) {
                $attribution->_toXml($attr);
            }
        }
        if ($this->_creator) {
            $root->addChild('creator', $this->getCreator());
        }
        if ($this->_date) {
            $root->addChild('date', $this->getDate());
        }
        if (count($this->_extensions)) {
            foreach ($this->_extensions as $extension) {
                $extension->_toXml($root);
            }
        }
        if ($this->_identifier) {
            $root->addChild('identifier', $this->getIdentifier());
        }
        if ($this->_image) {
            $root->addChild('image', $this->getImage());
        }
        if ($this->_info) {
            $root->addChild('info', $this->getInfo());
        }
        if ($this->_license) {
            $root->addChild('license', $this->getLicense());
        }
        if (count($this->_links)) {
            foreach ($this->_links as $link) {
                $link->_toXml($root);
            }
        }
        if ($this->_location) {
            $root->addChild('location', $this->getLocation());
        }
        if ($this->_title) {
            $root->addChild('title', $this->getTitle());
        }
        if (count($this->_tracks)) {
            $tracklist = $root->addChild('trackList');
            foreach ($this->_tracks as $track) {
                $track->_toXml($tracklist);
            }
        }
        return $tree->get();
    }
}
?>
