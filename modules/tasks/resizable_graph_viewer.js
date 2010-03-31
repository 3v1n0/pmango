/**
---------------------------------------------------------------------------

 PMango Project

 Title:      view TaskNetwork.

 File:       resizable.graph-viewer.js
 Location:   pmango/modules/tasks
 Started:    2010.02.09
 Author:     Marco Trevisan
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2010.01.27
   0.5, first version based on code rationaliztion from viewTN and viewWBS

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
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation Inc., 51 Franklin St, 5th Floor, Boston, MA 02110-1301 USA.

---------------------------------------------------------------------------
*/

var iviewer;

function viewerLoadPlaceHolder(container, img_src) {
	$(function() {
		cnt = $(container);
		if (!cnt) return;

		cnt.empty();
		cnt.iviewer({
	           src: img_src,
	           zoom: 100,
	           zoom_min: 100,
	           zoom_max: 100,
	           update_on_resize: true,
	           ui_disabled: true,
	           initCallback: function() {
	           	   if (iviewer)
	           	       iviewer.img_object.object.remove();

	               iviewer = this;
	           },
		       onStartDrag: function(object, coords) {
			       return false;
			   }
	      });
	});
}

function viewerLoadGraph(container, graph_src, graph_error) {
	$(function() {
		var img = new Image();
		
		$(img).load(function () {
	
			var zoom = "fit";
	
			if (img.width < $(container).width() && img.height < $(container).height())
				zoom = 100;
	
			$(container).empty();
			$(container).iviewer({
				   zoom: zoom,
		           src: img.src,
		           zoom_min: 5,
		           zoom_max: 1000,
		           update_on_resize: true,
		           ui_disabled: false,
		           initCallback: function() {		        	   
					   iviewer = this;
		           }
		      });
		})
	    .error(function () {
	    	viewerLoadPlaceHolder(container, graph_error);
	    })
	    .attr('src', graph_src);
	});
}

function updateIvewerStatus() {
	if (!iviewer || !iviewer.settings)
		return;

	iviewer.update_container_info();

	if (iviewer.settings.zoom == "fit")
		iviewer.fit();
	else
		iviewer.set_zoom(iviewer.current_zoom);
}

function createResizableViewer(container, content) {
	$(function() {
		$(container).resizable({
				minHeight: 300,
				minWidth: 400,
				alsoResize: content,
	
				stop: function(event, ui) {
					updateIvewerStatus();					
				}
		});
	});
}

function optionsSwitchCallBack(id1, id2, anim_speed, args) {
	if (!args || !args.container && !args.graph)
		return;
	
	var container = $(args.container);
	var graph = $(args.graph);
	var e1 = $('#'+id1);
	var e2 = $('#'+id2);
	var newheight = 0;

	if (e1.is(':visible'))
		newheight = container.height() - e2.height() + e1.height();
	else
		newheight = container.height() + e2.height() - e1.height();

	var maxY = container.position().top + Math.max(container.height(), newheight);
	
	if (maxY > $(window).height()*0.95) {
		container.animate(
			{height: newheight},
			anim_speed, function() {
				container.height(newheight);
				updateIvewerStatus();
		});
		graph.animate({height: newheight-2}, anim_speed);
	}
}