/**
---------------------------------------------------------------------------

 PMango Project

 Title:      javascript project utilities.

 File:       view.js
 Location:   pmango/modules/projects
 Started:    2009.11.11
 Author:     Marco Trevisan
 Type:       Javascript

 This file is part of the PMango project
 Further information at: http://penelope.di.unipi.it

 Version history.
 - 2010.02.08
   0.5, added a function for placing AJAX requests for Report forms
   0.4, added a function for placing AJAX requests for PDF forms
 - 2010.02.01
   0.3, use jQuery's slideToggle for switching item view
 - 2010.01.31
   0.2, jQuery toggle usage for projectViewSwitch
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

function projectViewSwitch() {
	
	var pr = $("#project_infos");
	var img = $("#project_expander_img");
	
	var pr_hidden = pr.is(":hidden");
	
	if (!$(".project_infos_childs").size()) { 
	  pr.children("td").each(function() {
	    $(this).wrapInner("<div class='project_infos_childs' />");
	    if (pr_hidden)
	    	$(this).children("div").hide();
	  });
	}
	
	var pr_c = $("div.project_infos_childs");
	
	if (pr_hidden) {
		img.attr('src', 'images/icons/collapse.gif');
		img.hide();
		img.fadeIn();
		pr.show();
		pr_c.slideDown();
	} else {
		img.attr('src', 'images/icons/expand.gif');
		img.hide();
		img.fadeIn();
		pr_c.slideUp(function() { pr.hide(); });
	}
	
//	$("#project_infos").toggle(
//		"1000",
//
//		function callback() {
//			if ($("#project_infos").is(":hidden"))
//				img.src = 'images/icons/expand.gif';
//			else
//				img.src = 'images/icons/collapse.gif';
//	});}

function displayItemSwitch(id1, id2) {
	
	$("#"+id1).slideToggle("normal");
	$("#"+id2).slideToggle("normal");

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

function generatePDF(form_id, parent_id) {
	var parent = $('#'+parent_id);
	var loader = parent_id+"_pdf_loader";
	var form = $("#"+form_id);
	
	
	parent.hide();
	parent.html('<img id="'+loader+'" src="images/ajax-loader.gif" alt="loader" />').fadeIn();
	
	$.ajax({
	   type: form.attr('method'),
	   url: form.attr('action'),
	   data: form.serialize(),
	   success: function(html) {
        	$("#"+loader).fadeOut("fast", function() {
            	var data = $(html).find('#'+parent_id).hide();
            	if (data.size() == 1) {
	            	parent.replaceWith(data);
	        		data.fadeIn("fast");
            	} else {
            		form.submit();
            	}
            });
  	   },
	   error: function() {
  		 	parent.hide();
  		 	form.submit();
  	   }
	});
}

function addReport(form_id, button_id) {

	var form = $("#"+form_id);
	var button = $("#"+button_id);
	var old_name = button.val();
	
	button.val("Loading...");
	
	$.ajax({
	   type: form.attr('method'),
	   url: form.attr('action'),
	   data: form.serialize(),
	   success: function() {
		   button.val("Done!");
		   setTimeout(function() {
			   button.fadeOut();
		   }, 500);
  	   },
	   error: function() {
  		   button.val(old_name);
  		   form.submit();
  	   }
	});
}

