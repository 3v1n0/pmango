<?php
/**
---------------------------------------------------------------------------

 Mango Project

 Title:      set english language of Mango

 File:       lang.php
 Location:   mango\locales\en
 Started:    2005.09.30
 Author:     dotProject Team
 Type:       PHP

 This file is part of the Mango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2006.07.18 Lorenzo
   First version, unmodified from dotProject 2.0.1.

---------------------------------------------------------------------------

 Mango - A web application for project planning and control.

 Copyright (C) 2006 Giovanni A. Cignoni, Lorenzo Ballini, Marco Bonacchi
 All rights reserved.

 Mango reuses part of the code of dotProject 2.0.1: dotProject code is 
 released under GNU GPL, further information at: http://www.dotproject.net
 Copyright (c) 2003-2005 The dotProject Development Team

 Other libraries used by Mango are redistributed under their own license.
 See ReadMe.txt in the root folder for details. 

 Mango is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation Inc., 51 Franklin St, 5th Floor, Boston, MA 02110-1301 USA

---------------------------------------------------------------------------
 */

// Entries in the LANGUAGES array are elements that describe the
// countries and language variants supported by this locale pack.
// Elements are keyed by the ISO 2 character language code in lowercase
// followed by an underscore and the 2 character country code in Uppercase.
// Each array element has 4 parts:
// 1. Directory name of locale directory
// 2. English name of language
// 3. Name of language in that language
// 4. Microsoft locale code

$dir = basename(dirname(__FILE__));

$LANGUAGES['en_AU'] = array ( $dir, 'English (Aus)', 'English (Aus)', 'ena');
$LANGUAGES['en_CA'] = array ( $dir, 'English (Can)', 'English (Can)', 'enc');
$LANGUAGES['en_GB'] = array ( $dir, 'English (GB)', 'English (GB)', 'eng');
$LANGUAGES['en_NZ'] = array ( $dir, 'English (NZ)', 'English (NZ)', 'enz');
$LANGUAGES['en_US'] = array ( $dir, 'English (US)', 'English (US)', 'enu', 'ISO8859-15');
?>
