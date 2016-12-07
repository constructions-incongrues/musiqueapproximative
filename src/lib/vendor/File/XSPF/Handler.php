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

/**
 * This class is used to parse an XSPF file.
 *
 * @category File
 * @package  File_XSPF
 * @author   David Grant <david@grant.org.uk>
 * @license  LGPL <http://www.gnu.org/licenses/lgpl.html>
 * @link     http://pear.php.net/package/File_XSPF
 */
class File_XSPF_Handler
{
    /**
     * An instance of the File_XSPF class.
     *
     * @access  private
     * @var     File_XSPF
     */
    var $_xspf;

    /**
     * The current hierachy of tags being handled.
     *
     * @access private
     * @var array
     */
    var $_tag_stack = array();

    /**
     * The currently open File_XSPF_Link instance.
     *
     * @access  private
     * @var     File_XSPF_Link
     */
    var $_curr_link;

    /**
     * The currently open File_XSPF_Meta instance.
     *
     * @access  private
     * @var     File_XSPF_Meta
     */
    var $_curr_meta;

    /**
     * The currently open File_XSPF_Track instance.
     *
     * @access  private
     * @var     File_XSPF_Track
     */
    var $_curr_track;

    /**
     * The currently open File_XSPF_Extension instance.
     *
     * @access  private
     * @var     File_XSPF_Extension
     */
    var $_curr_extn;
    
    /**
     * Constructor for the XML importing object.
     * 
     * This method is the default constructor for the File_XSPF class, and
     * uses functionality from the XML_Parser PEAR class to parse an XSPF
     * playlist into our class structure.
     *
     * @param File_XSPF $xspf an instance of the File_XSPF class.
     *
     * @access  public
     * @return  File_XSPF_Handler an instance of this class.
     */
    function __construct(File_XSPF $xspf)
    {
        $this->_xspf = $xspf;
    }

    /**
     * Handle character data from an XML file.
     * 
     * This method handles the character data that appears between tags in
     * the XSPF file.  If a tag has no attributes defined, this method
     * will be responsible for writing the tag directly, instead of suffering
     * the unnecessary overhead of handling the start and end tag events.
     *
     * @param resource $parser an instance of XML_Parser
     * @param string   $data   the character data to handle.
     *
     * @access  private
     * @return void
     */
    function cdataHandler($parser, $data)
    {
        // Because any valid markup can be written inside an extension
        // tag, we must first check the tag stack to see if we're inside
        // an open extension, and if so, write the data directly to that.
        if (in_array('EXTENSION', $this->_tag_stack)) {
            if (strlen(trim($data))) {
                $this->_curr_extn->_content = $data;
            }
            return;
        }
        // We're not inside an extension, so we can parse the data as
        // normal.
        $depth = count($this->_tag_stack);
        $path  = "/" . implode("/", $this->_tag_stack);
        switch (end($this->_tag_stack)) {
        case "ALBUM":
            if ($path == "/PLAYLIST/TRACKLIST/TRACK/ALBUM") {
                // This is the content of the track album.
                $this->_curr_track->setAlbum($data);
            } else {
                throw new File_XSPF_Exception('ALBUM element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "ANNOTATION":
            if ($path == "/PLAYLIST/ANNOTATION") {
                // This is the content of the playlist annotation.
                if ($this->_xspf->setAnnotation($data) == false) {
                    throw new File_XSPF_Exception($path . ' MUST NOT contain markup characters.', File_XSPF::ERROR_PARSING_FAILURE);
                }
            } elseif ($path == "/PLAYLIST/TRACKLIST/TRACK/ANNOTATION") {
                // This is the content of the track annotation.
                if ($this->_curr_track->setAnnotation($data) == false) {
                    throw new File_XSPF_Exception($path . ' MUST NOT contain markup characters.', File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('ANNOTATION element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "CREATOR":
            if ($path == "/PLAYLIST/CREATOR") {
                // This is the content of the playlist creator.
                $this->_xspf->setCreator($data);
            } elseif ($path == "/PLAYLIST/TRACKLIST/TRACK/CREATOR") {
                // This is the content of the track creator.
                $this->_curr_track->setCreator($data);
            } else {
                throw new File_XSPF_Exception('CREATOR element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "DATE":
            if ($path == "/PLAYLIST/DATE") {
                // This is the content of the playlist date.
                if (preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])T([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])([+-](0[0-9]|1[0-2]):[0-5][0-9])/", $data, $matches) == false) {
                    throw new File_XSPF_Exception($path . ' MUST be formatted as a XML Schema dateTime.', File_XSPF::ERROR_PARSING_FAILURE);
                }
                $this->_xspf->setDate($data);
            } else {
                throw new File_XSPF_Exception('DATE element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "DURATION":
            if ($path == "/PLAYLIST/TRACKLIST/TRACK/DURATION") {
                // This is the content of the track duration.
                if ($this->_curr_track->setDuration($data) === false) {
                    throw new File_XSPF_Exception("$path MUST contain a non-negative integer.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('DURATION element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "IDENTIFIER":
            $identifier = new File_XSPF_Identifier();
            if ($identifier->setUri($data) == false) {
                throw new File_XSPF_Exception("$path MUST contain a valid URN.", File_XSPF::ERROR_PARSING_FAILURE);
            }
            if ($path == "/PLAYLIST/IDENTIFIER") {
                // This is the content of the playlist identifier.
                $this->_xspf->setIdentifier($identifier);
            } elseif ($path == "/PLAYLIST/ATTRIBUTION/IDENTIFIER") {
                // This is the content of an attribution identifier.
                $this->_xspf->addAttribution($identifier);
            } elseif ($path == "/PLAYLIST/TRACKLIST/TRACK/IDENTIFIER") {
                // This is the content of the track identifier.
                $this->_curr_track->setIdentifier($identifier);
            } else {
                throw new File_XSPF_Exception('IDENTIFIER element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "IMAGE":
            if ($path == "/PLAYLIST/IMAGE") {
                // This is the content of the playlist image.
                if ($this->_xspf->setImage($data) == false) {
                    throw new File_XSPF_Exception("$path MUST contain a valid URL.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } elseif ($path == "/PLAYLIST/TRACKLIST/TRACK/IMAGE") {
                // This is the content of the track image.
                if ($this->_curr_track->setImage($data) == false) {
                    throw new File_XSPF_Exception("$path MUST contain a valid URL.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('IMAGE element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "INFO":
            if ($path == "/PLAYLIST/INFO") {
                // This is the content of the playlist info.
                if ($this->_xspf->setInfo($data) == false) {
                    throw new File_XSPF_Exception("$path MUST contain a valid URL.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } elseif ($path == "/PLAYLIST/TRACKLIST/TRACK/INFO") {
                // This is the content of the track info.
                if ($this->_curr_track->setInfo($data) == false) {
                    throw new File_XSPF_Exception("$path MUST contain a valid URL.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('INFO element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "LICENSE":
            if ($path == "/PLAYLIST/LICENSE") {
                // This is the content of the playlist license.
                if ($this->_xspf->setLicense($data) == false) {
                    throw new File_XSPF_Exception("$path MUST contain a valid URL.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('LICENSE element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "LINK":
            if ($path == "/PLAYLIST/LINK" || $path == "/PLAYLIST/TRACKLIST/TRACK/LINK") {
                // This is the content of a link element.
                if ($this->_curr_link->setContent($data) == false) {
                    throw new File_XSPF_Exception("$path MUST contain a valid URL.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('LINK element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "LOCATION":
            $location = new File_XSPF_Location();
            if ($location->setUrl($data) == false) {
                throw new File_XSPF_Exception("$path MUST contain a valid URL.", File_XSPF::ERROR_PARSING_FAILURE);
            }
            if ($path == "/PLAYLIST/LOCATION") {
                // This is the content of the playlist location.
                $this->_xspf->setLocation($location);
            } elseif ($path == "/PLAYLIST/ATTRIBUTION/LOCATION") {
                // This is the content of an attribution location.
                $this->_xspf->addAttribution($location);
            } elseif ($path == "/PLAYLIST/TRACKLIST/TRACK/LOCATION") {
                // This is the content of a track location.
                $this->_curr_track->addLocation($location);
            } else {
                throw new File_XSPF_Exception('LOCATION element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "META":
            if ($path == "/PLAYLIST/META" || $path == "/PLAYLIST/TRACKLIST/TRACK/META") {
                // This is the content of a meta element.
                $this->_curr_meta->setContent($data);;
            } else {
                throw new File_XSPF_Exception('META element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "TITLE":
            if ($path == "/PLAYLIST/TITLE") {
                // This is the content of the playlist title.
                $this->_xspf->setTitle($data);
            } elseif ($path == "/PLAYLIST/TRACKLIST/TRACK/TITLE") {
                // This is the content of thr track title.
                $this->_curr_track->setTitle($data);
            } else {
                throw new File_XSPF_Exception('TITLE element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "TRACKNUM":
            if ($path == "/PLAYLIST/TRACKLIST/TRACK/TRACKNUM") {
                // This is the content of the track number.
                if ($this->_curr_track->setTrackNumber($data) === false) {
                    throw new File_XSPF_Exception("$path MUST contain a non-negative integer.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('TRACKNUM element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        }
    }
    
    /**
     * Handle the closure of an XML tag.
     * 
     * This method handles to closure of an XML tag, generally tidying up
     * instances that were created in the startHandler method.
     *
     * @param resource $parser an instance of XML_Parser
     * @param string   $name   the closing tag to be handled (e.g. '</track>')
     *
     * @access  private
     * @return void
     */
    function endHandler($parser, $name)
    {
        // Any valid markup can be included inside an extension, so
        // we must ensure that it is handled properly, and doesn't
        // interfere with the standard elements.
        if (in_array('EXTENSION', $this->_tag_stack) && $name != 'EXTENSION') {
            $this->_curr_extn->_content = '</' . strtolower($name) . '>';
            return;
        }
        // This variable stores the current depth of the current tag.  This
        // is used for determining to which parent a shared element definition
        // belongs.
        $depth = count($this->_tag_stack);
        $path  = "/" . implode("/", $this->_tag_stack);
        switch ($name) {
        case "EXTENSION":
            // This is the end of the current extension element.  This
            // means we can now close it and add it to the right context.
            if ($path == "/PLAYLIST/EXTENSION") {
                // This extension element belongs to the /PLAYLIST/
                $this->_xspf->addExtension($this->_curr_extn);
            } elseif ($path == "/PLAYLIST/TRACKLIST/TRACK/EXTENSION") {
                // This extension element belongs to a TRACK/
                $this->_curr_track->addExtension($this->_curr_extn);
            } else {
                throw new File_XSPF_Exception('EXTENSION element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "LINK":
            // This is the end of the current link element.  This means
            // we can now close it and add it to the right context.
            if ($path == "/PLAYLIST/LINK") {
                // This link element belongs to the /PLAYLIST/
                $this->_xspf->addLink($this->_curr_link);
            } elseif ($path == "/PLAYLIST/TRACKLIST/TRACK/LINK") {
                // This link element belongs to a TRACK/
                $this->_curr_track->addLink($this->_curr_link);
            } else {
                throw new File_XSPF_Exception('LINK element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "META":
            // This is the end of the current meta element.  This means
            // we can now close it and add it to the right context.
            if ($path == "/PLAYLIST/META") {
                // This meta element belongs to the /PLAYLIST/
                $this->_xspf->addMeta($this->_curr_meta);
            } elseif ($path == "/PLAYLIST/TRACKLIST/TRACK/META") {
                // This meta element belongs to a TRACK/
                $this->_curr_track->addMeta($this->_curr_meta);
            } else {
                throw new File_XSPF_Exception('META element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "PLAYLIST":
            if ($path == "/PLAYLIST") {
                if ($this->_xspf->_count_tracklist != 1) {
                    throw new File_XSPF_Exception('/PLAYLIST MUST contain one TRACKLIST element.', File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('PLAYLIST element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "TRACK":
            if ($path == "/PLAYLIST/TRACKLIST/TRACK") {
                // This is the end of the current track element.  This means
                // we can now close it and add it to the /PLAYLIST/
                $this->_xspf->addTrack($this->_curr_track);
            } else {
                throw new File_XSPF_Exception('TRACK element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        }
        // Remove the current element from the tag stack.
        array_pop($this->_tag_stack);
    }

    /**
     * Handle the opening of an XML tag.
     * 
     * This method handles the opening of an XML tag in the XSPF playlist file.
     * The handler instantiates various support classes when it encounters
     * appropriate tags, which are later closed by the endHandler() method.
     *
     * @param resource $parser  an instance of XML_Parser
     * @param string   $name    the name of the tag being handled (e.g. '<track>')
     * @param array    $attribs a list of attributes for the tag.
     *
     * @access  private
     * @return void
     */
    function startHandler($parser, $name, $attribs)
    {
        // The extension element can contain any valid markup, so it
        // is handled up front to prevent mishandling of elements that
        // belong in the specification but appear under the extension
        // as well.
        if (in_array("EXTENSION", $this->_tag_stack)) {
            // Write out the tag name.
            $this->_curr_extn->_content .= '<' . strtolower(end($this->_tag_stack));
            // Iterate the tag attributes.
            if (count($attribs)) {
                foreach ($attribs as $param => $value) {
                    $this->_curr_extn->_content .= ' ' . strtolower($param) . '="' . $value . '"';
                }
            }
            $this->_curr_extn->_content .= '>';
            return;
        }

        $path = "/" . implode("/", $this->_tag_stack);

        switch ($name) {
        case "ALBUM":
            if ($path == "/PLAYLIST/TRACKLIST/TRACK") {
                $this->_curr_track->_count_album++;
                if ($this->_curr_track->_count_album++ > 1) {
                    throw new File_XSPF_Exception("$path contains too many ALBUM elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('ALBUM element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "ANNOTATION":
            switch ($path) {
            case "/PLAYLIST":
                ++$this->_xspf->_count_annotation;
                if ($this->_xspf->_count_annotation > 1) {
                    throw new File_XSPF_Exception("$path contains too many ANNOTATION elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
                break;
            case "/PLAYLIST/TRACKLIST/TRACK":
                ++$this->_curr_track->_count_annotation;
                if ($this->_curr_track->_count_annotation > 1) {
                    throw new File_XSPF_Exception("$path contains too many ANNOTATION elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
                break;
            default:
                throw new File_XSPF_Exception('ANNOTATION element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "ATTRIBUTION":
            if ($path == "/PLAYLIST") {
                ++$this->_xspf->_count_attribution;
                if ($this->_xspf->_count_attribution > 1) {
                    throw new File_XSPF_Exception("$path contains too many ATTRIBUTION elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('ATTRIBUTION element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "CREATOR":
            switch ($path) {
            case "/PLAYLIST":
                ++$this->_xspf->_count_creator;
                if ($this->_xspf->_count_creator > 1) {
                    throw new File_XSPF_Exception("$path contains too many CREATOR elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
                break;
            case "/PLAYLIST/TRACKLIST/TRACK":
                ++$this->_curr_track->_count_creator;
                if ($this->_curr_track->_count_creator > 1) {
                    throw new File_XSPF_Exception("$path contains too many CREATOR elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
                break;
            default:
                throw new File_XSPF_Exception('CREATOR element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "DATE":
            if ($path == "/PLAYLIST") {
                ++$this->_xspf->_count_date;
                if ($this->_xspf->_count_date > 1) {
                    throw new File_XSPF_Exception("$path contains too many DATE elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('DATE element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "DURATION":
            if ($path == "/PLAYLIST/TRACKLIST/TRACK") {
                ++$this->_curr_track->_count_duration;
                if ($this->_curr_track->_count_duration > 1) {
                    throw new File_XSPF_Exception("$path contains too many DURATION elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('DURATION element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "EXTENSION":
            if ($path == "/PLAYLIST" || $path == "/PLAYLIST/TRACKLIST/TRACK") {
                // This is the start of a extension object.  The element
                // is stored in an object parameter to allow adding of
                // extension content.
                $this->_curr_extn = new File_XSPF_Extension();
                if (isset($attribs['APPLICATION'])) {
                    if ($this->_curr_extn->setApplication($attribs['APPLICATION']) == false) {
                        throw new File_XSPF_Exception("$path/EXTENSION attribute APPLICATION MUST contain a valid URI.", File_XSPF::ERROR_PARSING_FAILURE);
                    }
                }
            } else {
                throw new File_XSPF_Exception('EXTENSION element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "IDENTIFIER":
            if ($path == "/PLAYLIST") {
                ++$this->_xspf->_count_identifier;
                if ($this->_xspf->_count_identifier > 1) {
                    throw new File_XSPF_Exception("$path contains too many IDENTIFIER elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } elseif ($path != "/PLAYLIST/ATTRIBUTION" && $path != "/PLAYLIST/TRACKLIST/TRACK") {
                throw new File_XSPF_Exception('IDENTIFIER element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "IMAGE":
            switch ($path) {
            case "/PLAYLIST":
                $this->_xspf->_count_image++;
                if ($this->_xspf->_count_image > 1) {
                    throw new File_XSPF_Exception("$path contains too many IMAGE elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
                break;
            case "/PLAYLIST/TRACKLIST/TRACK":
                $this->_curr_track->_count_image++;
                if ($this->_curr_track->_count_image > 1) {
                    throw new File_XSPF_Exception("$path contains too many IMAGE elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
                break;
            default:
                throw new File_XSPF_Exception('IMAGE element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "INFO":
            switch ($path) {
            case "/PLAYLIST":
                $this->_xspf->_count_info++;
                if ($this->_xspf->_count_info > 1) {
                    throw new File_XSPF_Exception("$path contains too many INFO elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
                break;
            case "/PLAYLIST/TRACKLIST/TRACK":
                $this->_curr_track->_count_info++;
                if ($this->_curr_track->_count_info > 1) {
                    throw new File_XSPF_Exception("$path contains too many INFO elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
                break;
            default:
                throw new File_XSPF_Exception('INFO element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "LICENSE":
            if ($path == "/PLAYLIST") {
                ++$this->_xspf->_count_license;
                if ($this->_xspf->_count_license > 1) {
                    throw new File_XSPF_Exception("$path contains too many LICENSE elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('LICENSE element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "LINK":
            if ($path == "/PLAYLIST" || $path == "/PLAYLIST/ATTRIBUTION" || $path == "/PLAYLIST/TRACKLIST/TRACK") {
                // This is the start of a link element.  The element is
                // stored in an object parameter to allow adding of link
                // content.
                $this->_curr_link = new File_XSPF_Link();
                if (isset($attribs['REL'])) {
                    if ($this->_curr_link->setRelationship($attribs['REL']) == false) {
                        throw new File_XSPF_Exception("$path/LINK attribute REL MUST contain a valid URL.", File_XSPF::ERROR_PARSING_FAILURE);
                    }
                }
            } else {
                throw new File_XSPF_Exception('LINK element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "LOCATION":
            if ($path == "/PLAYLIST") {
                ++$this->_xspf->_count_location;
                if ($this->_xspf->_count_location > 1) {
                    throw new File_XSPF_Exception("$path contains too many LOCATION elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } elseif ($path != "/PLAYLIST/ATTRIBUTION" && $path != "/PLAYLIST/TRACKLIST/TRACK") {
                throw new File_XSPF_Exception('LOCATION element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
        case "META":
            if ($path == "/PLAYLIST" || $path == "/PLAYLIST/ATTRIBUTION" || $path == "/PLAYLIST/TRACKLIST/TRACK") {
                // This is the start of a meta element.  The element is
                // stored in an object parameter to allow adding of meta
                // content.
                $this->_curr_meta = new File_XSPF_Meta();
                if (isset($attribs['REL'])) {
                    if ($this->_curr_meta->setRelationship($attribs['REL']) == false) {
                        throw new File_XSPF_Exception("$path/META attribute REL MUST contain a valid URL.", File_XSPF::ERROR_PARSING_FAILURE);
                    }
                }
            } else {
                throw new File_XSPF_Exception('META element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "PLAYLIST":
            if ($path == "/") {
                // This is the start of a playlist element.
                if (isset($attribs['VERSION'])) {
                    if ($attribs['VERSION'] != 0 && $attribs['VERSION'] != 1) {
                        throw new File_XSPF_Exception('/PLAYLIST attribute VERSION MUST be 1 or 0.', File_XSPF::ERROR_PARSING_FAILURE);
                    } else {
                        $this->_xspf->_version = $attribs['VERSION'];
                    }
                } else {
                    throw new File_XSPF_Exception('/PLAYLIST MUST have a VERSION attribute.', File_XSPF::ERROR_PARSING_FAILURE);
                }
                if (isset($attribs['XMLNS'])) {
                    if ($attribs['XMLNS'] != 'http://xspf.org/ns/0/') {
                        throw new File_XSPF_Exception('/PLAYLIST attribute NAMESPACE is invalid.', File_XSPF::ERROR_PARSING_FAILURE);
                    } else {
                        $this->_xspf->_xmls = $attribs['XMLNS'];
                    }
                } else {
                    throw new File_XSPF_Exception('/PLAYLIST MUST have a XMLNS attribute.', File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('PLAYLIST element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "TITLE":
            switch ($path) {
            case "/PLAYLIST":
                $this->_xspf->_count_title++;
                if ($this->_xspf->_count_title > 1) {
                    throw new File_XSPF_Exception('/PLAYLIST contains too many TITLE elements.', File_XSPF::ERROR_PARSING_FAILURE);
                }
                break;
            case "/PLAYLIST/TRACKLIST/TRACK":
                $this->_curr_track->_count_title++;
                if ($this->_curr_track->_count_title > 1) {
                    throw new File_XSPF_Exception("$path contains too many TITLE elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
                break;
            default:
                throw new File_XSPF_Exception('TITLE element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "TRACK":
            // This is the start of a track element.  The element is
            // stored in an object parameter to allow adding of sub-
            // elements to the TRACK/
            if ($path == "/PLAYLIST/TRACKLIST") {
                $this->_curr_track = new File_XSPF_Track();
            } else {
                throw new File_XSPF_Exception('TRACK element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "TRACKNUM":
            if ($path == "/PLAYLIST/TRACKLIST/TRACK") {
                ++$this->_curr_track->_count_tracknum;
                if ($this->_curr_track->_count_tracknum > 1) {
                    throw new File_XSPF_Exception("$path contains too many TRACKNUM elements.", File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('TRACKNUM element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        case "TRACKLIST":
            if ($path == "/PLAYLIST") {
                $this->_xspf->_count_tracklist++;
                if ($this->_xspf->_count_tracklist > 1) {
                    throw new File_XSPF_Exception('/PLAYLIST contains too many TRACKLIST elements.', File_XSPF::ERROR_PARSING_FAILURE);
                }
            } else {
                throw new File_XSPF_Exception('TRACKLIST element found in illegal position.', File_XSPF::ERROR_PARSING_FAILURE);
            }
            break;
        default:
            if (count($this->_tag_stack)) {
                $parent = end($this->_tag_stack);

                throw new File_XSPF_Exception("$path MUST NOT contain HTML.", File_XSPF::ERROR_PARSING_FAILURE);
            }
        }
        // Add the current element to the tag stack.
        array_push($this->_tag_stack, $name);
    }
}
?>
