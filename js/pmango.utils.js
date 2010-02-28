/**
---------------------------------------------------------------------------

 PMango Project

 Title:      javascript pmango utilities.

 File:       view.js
 Location:   js
 Started:    2009.11.11
 Author:     Marco Trevisan
 Type:       Javascript

 This file is part of the PMango project
 Further information at: http://penelope.di.unipi.it

 Version history.
 - 2010.02.10
   0.7 added resizeItemToVisible()
   0.6 callback support on displayItemSwitch()
 - 2010.02.08
   0.5, added a function for placing AJAX requests for Report forms
   0.4, added a function for placing AJAX requests for PDF forms
 - 2010.02.01
   0.3, use jQuery's slideToggle for switching item view
 - 2009.11.11 Marco Trevisan
   0.1, added some utility functions
   
---------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2009-2010 Marco Trevisan (Treviño) <mail@3v1n0.net>
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

function displayItemSwitch(id1, id2, pre_callback, pre_args, post_callback, post_args) {
	var anim_speed = "normal";
	
	if (typeof displayItemSwitchPreCallback == 'function') {
		if (typeof displayItemSwitchPreCallbackARGS == 'undefined')
			args = null;
		else
			args = displayItemSwitchPreCallbackARGS;

		displayItemSwitchPreCallback(id1, id2, anim_speed, args);
	}

	if (typeof pre_callback == 'function') {
		pre_callback(id1, id2, anim_speed, pre_args == undefined ? null : pre_args);
	}
	
	$("#"+id1).slideToggle(anim_speed);
	$("#"+id2).slideToggle(anim_speed, function() {
		if (typeof displayItemSwitchPostCallback == 'function') {
			if (typeof displayItemSwitchPostCallbackARGS == 'undefined')
				args = null;
			else
				args = displayItemSwitchPostCallbackARGS;

			displayItemSwitchPostCallback(id1, id2, anim_speed, args);
		}
		
		if (typeof post_callback == 'function') {
			post_callback(id1, id2, anim_speed, post_args == undefined ? null : post_args);
		}
	});

	/*
	
	switchItemDisplay(id1);
	switchItemDisplay(id2);
	return;
	
	// Alternative:
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
	}
	*/
}

function addAJAX(form_selector) {
	var form = $(form_selector);
	var ajax = $(form_selector+" input[name=ajax]");

	if (!ajax.size())
		form.append('<input type="hidden" name="ajax" value="true" />');
	else
		ajax.val("true");
}

function delAJAX(form_selector) {
	var form = $(form_selector);
	var ajax = $(form_selector+" input[name=ajax]");

	if (ajax.size())
		ajax.remove();
}

function topMsgUpdate(updated_parent) {
	var msg = $("#ui_top_message:first");
	var new_msg = $(updated_parent).find("#ui_top_message:first");

	if (new_msg.size())
		new_msg.hide();
	
	msg.fadeOut(function() {
		if (new_msg.size() == 1) {
			msg.replaceWith(new_msg);
			new_msg.fadeIn();
		}
	});
}

function resizeItemToVisible(item, percent, animation, top_start) {
	$(function() {
		if (!item) return;
		if (percent > 1 || percent < 0) percent = 1.0;
		if (top_start === undefined) top_start = 0;
		if (animation === undefined) animation = false;
		
		var obj = $(item);
		if (!obj.size()) return;
		
		var itemWidth = $(window).width() * percent;
		var itemHeight = ($(window).height() - obj.position().top - top_start) * percent;
		if (animation == true) {
			obj.animate({
				width: itemWidth,
			    height: itemHeight
			});
		} else {
			obj.width(itemWidth);
			obj.height(itemHeight);
		}
	});
}
