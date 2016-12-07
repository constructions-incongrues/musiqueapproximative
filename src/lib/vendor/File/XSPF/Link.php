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
 * This class is the objectification of an XSPF Link element.
 *
 * @category File
 * @package  File_XSPF
 * @author   David Grant <david@grant.org.uk>
 * @license  LGPL <http://www.gnu.org/licenses/lgpl.html>
 * @link     http://pear.php.net/package/File_XSPF
 */
class File_XSPF_Link
{
    /**
     * The URL of a non-XSPF web resource.
     *
     * @access  private
     * @var     string
     */
    var $_content;
    /**
     * The URI of a non-XSPF resource type.
     *
     * @access  private
     * @var     string
     */
    var $_rel;
    
    /**
     * Get the content of this link object.
     * 
     * This method returns the content of this link object, which should be the
     * URL of a resource following the specification stored in the relationship
     * attribute.
     *
     * @access public
     * @return string the URL of a non-XSPF web resource.
     */
    function getContent()
    {
        return $this->_content;
    }

    /**
     * Get the relationship of this link object.
     * 
     * This method returns the valid URI used to define the purpose of the
     * content of this link element.
     *
     * @access public
     * @return string the URI of this element's content purpose definition.
     */
    function getRelationship()
    {
        return $this->_rel;
    }

    /**
     * Set the content of this link element.
     * 
     * This method sets the content of this link element, which should the be
     * URL of a non-XSPF web resource, such as an RDF document.
     *
     * @param string $content the URL of a non-XSPF web resource.
     *
     * @access public
     * @return bool
     */
    function setContent($content)
    {
        if (File_XSPF::_validateURL($content)) {
            $this->_content = $content;
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Set the relationship of this link element.
     * 
     * This method sets the URI of a resource used to define the purpose of the
     * URL used as the content for this link element.
     *
     * @param string $rel the URI of a resource description definition.
     *
     * @access public
     * @return bool
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
    function _toXml(&$parent)
    {
        if ($this->getRelationship()) {
            $parent->addChild('link', $this->getContent(),
                                      array('rel' => $this->getRelationship()));
        } else {
            $parent->addChild('link', $this->getContent());
        }
    }
}
