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
 * This class is the objectification of an XSPF Identifier element.
 *
 * @category File
 * @package  File_XSPF
 * @author   David Grant <david@grant.org.uk>
 * @license  LGPL <http://www.gnu.org/licenses/lgpl.html>
 * @link     http://pear.php.net/package/File_XSPF
 */
class File_XSPF_Identifier
{
    /**
     * a valid URI.
     *
     * @access  private
     * @var     string
     */
    var $_uri;

    /**
     * Set the URI for this class.
     * 
     * This constructor provides an opportunity to set the URI for this identifier
     * instead of instantiating the class and using the setUri method.
     *
     * @param string $uri a valid URI
     *
     * @access  public
     * @return  File_XSPF_Identifier
     */
    function File_XSPF_Identifier($uri = null)
    {
        if (! is_null($uri)) {
            $this->setUri($uri);
        }
    }
    
    /**
     * Get the URI for this identifier.
     * 
     * This method returns the URI for this identifier, which is the canonical ID
     * for this resource.
     *
     * @access  public
     * @return  string a valid URI.
     */
    function getUri()
    {
        return $this->_uri;
    }
    
    /**
     * Set the URI for this identifier.
     * 
     * This method sets the URI of this identifier. If the parameter is not a valid
     * URI, this method will fail.
     *
     * @param string $uri a valid URI
     *
     * @access  public
     * @return bool
     */
    function setUri($uri)
    {
        if (File_XSPF::_validateUrn($uri)) {
            $this->_uri = $uri;
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
     * @access  private
     * @return void
     */
    function _toXML($parent)
    {
        $parent->addChild('identifier', $this->getUri());
    }
}
?>
