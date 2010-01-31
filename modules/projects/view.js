/**
---------------------------------------------------------------------------

 PMango Project

 Title:      task view.

 File:       view.js
 Location:   pmango/modules/projects
 Started:    2009.11.11
 Author:     Marco Trevisan
 Type:       Javascript

 This file is part of the PMango project
 Further information at: http://penelope.di.unipi.it

 Version history. 
 - 2009.11.11 Marco Trevisan
   First version, added some utility functions
   
---------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2009 Marco Trevisan
 All rights reserved.

 PMango reuses part of the code of dotProject 2.0.1: dotProject code is 
 released under GNU GPL, further information at: http://www.dotproject.net
 Copyright (C) 2003-2005 The dotProject Development Team.

 Other libraries used by PMango are redistributed under their own license.
 See ReadMe.txt in the root folder for details. 

 PMango is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation Inc., 51 Franklin St, 5th Floor, Boston, MA 02110-1301 USA.

---------------------------------------------------------------------------
*/

function showItem(id, show) {
	if (id) {
		if (show)
			document.getElementById(id).style.display = '';
		else
			document.getElementById(id).style.display = 'none';
	}
}

function switchItemDisplay(id) {
	if (!id) return;
	
	var item = document.getElementById(id);

	if (item) {
		if (item.style.display == 'none') {
			item.style.display = '';
			return true;
		} else {
			item.style.display = 'none';
			return false;
		}	
	}
}

function projectViewSwitch() {
	var img = document.getElementById('project_expander_img');
	
	$("#project_infos").toggle(
		"fast",

		function callback() {
			if ($("#project_infos").is(":hidden"))
				img.src = 'images/icons/expand.gif';
			else
				img.src = 'images/icons/collapse.gif';
	});}

function displayItemSwitch(id1, id2) {
	switchItemDisplay(id1);
	switchItemDisplay(id2);
	/*
	var item1 = document.getElementById(id1);
	var item2 = document.getElementById(id2);

	if (!id1 || !item1)
		return;

	if (item1.style.display != 'none') {
		item1.style.display = 'none';

		if (id2 && item2)
			item2.style.display = '';
	} else {
		item1.style.display = '';

		if (id2 && item2)
			item2.style.display = 'none';
	}*/
}

