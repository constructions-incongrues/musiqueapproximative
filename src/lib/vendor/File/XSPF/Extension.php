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
 * This class is the objectification of an XSPF Extension element.
 *
 * @category File
 * @package  File_XSPF
 * @author   David Grant <david@grant.org.uk>
 * @license  LGPL <http://www.gnu.org/licenses/lgpl.html>
 * @link     http://pear.php.net/package/File_XSPF
 */
class File_XSPF_Extension
{
    /**
     * The URI of a resource describing the extension content format.
     *
     * @access  private
     * @var     string
     */
    var $_application;

    /**
     * Non-XSPF XML data used to describe an extension to this playlist.
     *
     * @access  private
     * @var     string
     */
    var $_content;

    /**
     * Get the application URI for this extension.
     * 
     * This method returns a URI of a resource used to define the structure and
     * purpose of the extension content.
     *
     * @access  public
     * @return  string the URI of the resource used to define the extension content.
     */
    function getApplication()
    {
        return $this->_application;
    }
    
    /**
     * Get the content of this extension.
     * 
     * This method returns the content of this extension object, which will be
     * is be a string of xml data.
     *
     * @access  public
     * @return  string  an XML document fragment.
     */
    function getContent()
    {
        return $this->_content;
    }
    
    /**
     * Set the URI of the resource to define this extension.
     * 
     * This method sets the URI of the resource used to define the structure and
     * purpose of this extension content.
     *
     * @param string $application the URI of the application definition.
     *
     * @access  public
     * @return bool
     */
    function setApplication($application)
    {
        if (File_XSPF::_validateUri($application)) {
            $this->_application = $application;
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Set the content of this extension.
     * 
     * This method sets the content of this extension.  The supplied string
     * should be valid XML data.
     *
     * @param string $content a valid XML document fragment.
     *
     * @access  public
     * @return void
     */
    function setContent($content)
    {
        $this->_content = $content;
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
    function _toXml($parent)
    {
        if ($this->getApplication()) {
            $options = array('application' => $this->getApplication());
            $parent->addChild('extension', $this->getContent(), $options);
        } else {
            $parent->addChild('extension', $this->getContent());
        }
    }
}
