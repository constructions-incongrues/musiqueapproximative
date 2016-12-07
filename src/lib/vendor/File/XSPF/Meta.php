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
 * This class is the objectification of an XSPF Meta element.
 *
 * @category File
 * @package  File_XSPF
 * @author   David Grant <david@grant.org.uk>
 * @license  LGPL <http://www.gnu.org/licenses/lgpl.html>
 * @link     http://pear.php.net/package/File_XSPF
 */
class File_XSPF_Meta
{
    /**
     * Value of this metadata element.
     *
     * @access  private
     * @var     string
     */
    var $_content;

    /**
     * The URI of a resource defining the metadata.
     *
     * @access  private
     * @var     string
     */
    var $_rel;
    
    /**
     * Get the content of this metadata object.
     * 
     * This method returns the content of this metadata object, which is a plain
     * text node used to convey a single item of information.
     *
     * @access  public
     * @return  string a plain text metadata value.
     */
    function getContent()
    {
        return $this->_content;
    }
    
    /**
     * Get the URL of this metadata definition resource.
     * 
     * This method returns the URL of the resource used to define the purpose
     * of this metadata element.
     *
     * @access  public
     * @return  string  a URI of a metadata definition resource.
     */
    function getRelationship()
    {
        return $this->_rel;
    }
    
    /**
     * Set the content of this metadata element.
     * 
     * This method sets the content of this metadata element.  The content
     * should be a plain text value, and should not contain any markup.
     *
     * @param string $content the plain text metadata value.
     *
     * @access public
     * @return void
     */
    function setContent($content)
    {
        $this->_content = $content;
    }
    
    /**
     * Set the URI used to define this metadata element.
     * 
     * This method sets the URI of the resource used to define the purpose of 
     * this metadata element.
     *
     * @param string $rel the valid URI of a definition resource.
     *
     * @access public
     * @return void
     */
    function setRelationship($rel)
    {
        if (File_XSPF::_validateUri($rel)) {
            $this->_rel = $rel;
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
        if ($this->getRelationship()) {
            $parent->addChild('meta', $this->getContent(),
                                      array('rel' => $this->getRelationship()));
        } else {
            $parent->addChild('meta', $this->getContent());
        }
    }
}
?>
