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
 - 2010.02.10
   0.3, ajax functions for retriving project data
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

function settingsTabToggle() {
	displayItemSwitch("tab_content", "tab_settings_content");
}

function projectViewSwitch() {
	
	var pr = $("#project_infos");
	var img = $("#project_expander_img");
	
	var pr_hidden = pr.is(":hidden");
	
	if (!$("div.project_infos_childs").size()) { 
	  pr.children("td").each(function() {
	    $(this).wrapInner("<div class='project_infos_childs' />");
	    if (pr_hidden)
	    	$(this).children("div").hide();
	  });
	}
	
	var pr_c = $("div.project_infos_childs");
	
	if (pr_hidden) {
		img.fadeOut(250, function() {
			img.attr('src', 'images/icons/collapse.gif');
			img.fadeIn();
		});
		
		pr.show();
		pr_c.slideDown();
	} else {
		img.fadeOut(250, function() {
			img.attr('src', 'images/icons/expand.gif');
			img.fadeIn();
		});

		pr_c.slideUp(function() { pr.hide(); });
	}
	
//	pr.toggle(
//		"1000",
//
//		function callback() {
//			if (pr.is(":hidden"))
//				img.src = 'images/icons/expand.gif';
//			else
//				img.src = 'images/icons/collapse.gif';
//	});}

function generatePDF(form_id, parent_id) {
	var parent = $('#'+parent_id);
	var loader = parent_id+"_pdf_loader";
	var form = $("#"+form_id);
	
	if (!form.size())
		return;
	
	if (!parent.size()) {
		form.submit();
		return;
	}
	
	addAJAX("#"+form_id);
	
	parent.hide();
	parent.html('<img id="'+loader+'" src="style/default/images/ajax-loader.gif" alt="loader" />').fadeIn();
	
	$.ajax({
	   type: form.attr('method'),
	   url: form.attr('action'),
	   data: form.serialize(),
	   success: function(html) {
        	$("#"+loader).fadeOut("fast", function() {
        		topMsgUpdate(html);
        		
            	var data = $(html).find('#'+parent_id).hide();
            	if (data.size() == 1) {
	            	parent.replaceWith(data);
	        		data.fadeIn("fast");
            	} else {
            		delAJAX("#"+form_id);
            		form.submit();
            	}
            });
  	   },
	   error: function() {
  		   	delAJAX("#"+form_id);
  		 	parent.hide();
  		 	form.submit();
  	   }
	});
	
	delAJAX("#"+form_id);
}

function addReport(form_id, button_id) {
	var form = $("#"+form_id);
	var button = $("#"+button_id);
	var old_name = button.val();
	
	if (!form.size())
		return;
	
	if (!button.size()) {
		form.submit();
		return;
	}
	
	addAJAX("#"+form_id);
	button.val("Loading...");
	
	$.ajax({
	   type: form.attr('method'),
	   url: form.attr('action'),
	   data: form.serialize(),
	   success: function(html) {
		   topMsgUpdate(html);
		   var data = $(html).find('#'+button_id);

		   if (!data.size()) {
			   button.val("Error, reloading...");
			   delAJAX("#"+form_id);
			   form.submit();
		   } else {
			   button.val("Done!");
			   setTimeout(function() {
				   button.fadeOut(function() {
					   button.val(old_name);
				   });
			   }, 500);
		   }
  	   },
	   error: function() {
  		   button.val(old_name);
  		   delAJAX("#"+form_id);
  		   form.submit();
  	   }
	});
	
	delAJAX("#"+form_id);
}

function updateTabContent(form_id, content_id, pdf_span_id, report_button_id) {
	var form = $("#"+form_id);
	var content = $("#"+content_id);
	var old_content = content.clone().text();
	var content_loader_id = content_id+"_loader";

	addAJAX("#"+form_id);

	content.animate({
			height: "toggle",
			opacity: "toggle"
		},

		function() {
			content.html('<img id="'+content_loader_id+'" src="style/default/images/ajax-loader-horizontal.gif" alt="loader" />')
				   .attr('align', 'center')
			       .css('padding-top', '20px')
			       .css('padding-bottom', '20px')
			       .slideDown();
	 
			$.ajax({
			   type: form.attr("method"),
			   url:  form.attr("action"),
			   data: form.serialize(),
			   success: function(html) {
		        	$("#"+content_loader_id).fadeOut("fast", function() {
		
						topMsgUpdate(html);

						if (!$(html).find("#"+pdf_span_id).children().size()) {
							$("#"+pdf_span_id).empty();
						}
		
		        		var newdata = $(html).find("#"+content_id);
		        		newdata.hide();
		
		        		if (newdata.size() == 1) {
		        			content.replaceWith(newdata);
		        			newdata.animate({
		        				height: "toggle",
		        				opacity: "toggle"
		        			});
		
		        			if (old_content != newdata.clone().text()) {
		        				$("#"+report_button_id).show();
		        			}
		
		        		} else {
		        			delAJAX("#"+form_id);
		            		form.submit();
		            		return;
		        		}
		            });
		  	   },
		  	   error: function() {
		  		   	delAJAX("#"+form_id);
		  		 	form.submit();
		  	   }
			});
		}
	);
	
	delAJAX("#"+form_id);
}