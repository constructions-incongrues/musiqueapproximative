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
 * This class is the objectification of an XSPF Location element.
 *
 * @category File
 * @package  File_XSPF
 * @author   David Grant <david@grant.org.uk>
 * @license  LGPL <http://www.gnu.org/licenses/lgpl.html>
 * @link     http://pear.php.net/package/File_XSPF
 */
class File_XSPF_Location
{
    /**
     * a valid URL
     *
     * @access  private
     * @var     string
     */
    var $_url;
    
    /**
     * Set the URL of this location.
     * 
     * This method is the constructor for this class, and provides a way
     * to avoid instantiating the class and calling the setURL method.
     *
     * @param string $url Location
     *
     * @access  public
     * @return  File_XSPF_Location
     */
    function File_XSPF_Location($url = null)
    {
        if (! is_null($url)) {
            $this->setUrl($url);
        }
    }

    /**
     * Get the URL for this location.
     * 
     * This method returns the URL for this location object.
     *
     * @access  public
     * @return  string a valid URL
     */
    function getUrl()
    {
        return $this->_url;
    }
    
    /**
     * Sets the location data.
     * 
     * This method sets the URL of this location element. The location parameter must
     * be a valid URL.
     *
     * @param string $url a valid URL
     *
     * @access  public
     * @return bool
     */
    function setUrl($url)
    {
        if (File_XSPF::_validateUrl($url)) {
            $this->_url = $url;
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
     * @param XML_Tree_Node &$parent Parent node
     *
     * @access private
     * @return void
     */
    function _toXml($parent)
    {
        $parent->addChild('location', $this->getUrl());
    }
}
?>
