var tasklists = new tasklists();

function tasklists()
{
	this.Mode = 'tasklists';
	this.Loading = false;
	this.MovingEl = null;
	this.FocusedId = null;
    this.IsFirefox = false;
	this.DefaultText = null;
	this.SettingsId = null;
	this.CurrentDate = null;
	this.ActiveTags = new Array();
    this.WeekCount = 1;
	//this.EnableLayoutCorrection = null;
	

	this.LoadTaskListsCallback = function(html, callback)
	{
		if (!html)
        	tasklists.LoadTaskLists(callback, tasklists.CurrentDate);
		else
		{
			tasklists.Mode = null;
            document.getElementById('paginator').style.display = 'none';
	    	document.getElementById('contents').innerHTML = '<div class="message">' + html + '</div>';
		}
	}


	this.Initialize = function(account)
	{
	    core.Account = account;
	    this.LoadTaskLists();
        this.IsFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') >= 0;
	    Element.prototype.getText = function() { return this.innerText ? this.innerText : this.textContent; };
	    Element.prototype.setText = function(value) { return this.innerText ? this.innerText = value : this.textContent = value; };

     	window.onresize = function() { if (!tasklists.Loading) tasklists.LoadTaskLists(null, tasklists.CurrentDate); };
	    document.onmousedown = function(e)
	    {
	    	var el = core.GetEl(e);
    		if (!el.tagName)
	  			return true;

	    	if (calendar.Element != null)
	    	{
		    	if (core.GetParentByClassName(el, 'calendar'))
	    			return;
	    		else if (calendar.ParentElement != el)
	    	    	calendar.Close();
	    	}
			
			var p = core.GetParentByClassName(el, 'editable');
			if (tasklists.FocusedId != null && (p == null || p.id != tasklists.FocusedId))
			{
				endEdit();
				tasklists.FocusedId = null;
			}

	    	if (el.className.indexOf('movable') >= 0)
            {
            	var x = e.clientX ? e.clientX : e.x;
            	var y = e.clientY ? e.clientY : e.y;
				pickListUp(el, x, y);
                return false;
            }

			var editCallback = function()
			{
            	if (p != null && p.id != tasklists.FocusedId && el.href == null)
				{
	            	beginEdit(p);
	            	return true;
	            }
			}

			if (tasklists.IsFirefox)
				document.onclick = editCallback;
			else
                editCallback();
	    };
	}


	this.ShowUserSettings = function(el)
	{
    	var cnt = document.getElementById('userSettings');
		if (cnt)
			cnt.style.display = '';
		else
		{
			el.id = 'popupButton';
			el.className = 'active';
			core.CallApi("GetUserSettings", function(html)
			{
	        	document.getElementById('contents').innerHTML += html;
	            document.getElementById(el.id).className = null;
			});
		}
	}


	this.ClosePopup = function(el)
	{
		var el = core.GetParentByClassName(el, 'popup');
		if (el)
			el.style.display = 'none';
	}


	this.LoadTaskLists = function(callback, dt)
	{
		if (!tasklists.Mode)
			return;

	    tasklists.Loading = true;
	    var now = new Date(new Date().toDateString());
		if (!dt)
			dt = now;

		var cnts = document.getElementById('contents');
		core.CallApi('GetTaskLists&d=' + parseInt((dt.getTime() - now.getTime()) / 3600000 / 24) + '&t=' + parseInt(-dt.getTimezoneOffset() / 60) + '&o=' + tasklists.Mode + '&w=' + cnts.clientWidth + '&c=' + this.WeekCount, function(html)
		{
			cnts.innerHTML = html;
			tasklists.SettingsId = null;
		    tasklists.CurrentDate = new Date(document.getElementById('selecteddate').getText());
            document.getElementById('paginator').style.display = '';
		    document.getElementById('datename').setText(tasklists.CurrentDate.toDateString());
		    document.getElementById('today').className = tasklists.CurrentDate.toDateString() == now.toDateString() ? 'active' : '';

		    if (tasklists.Mode == 'tasklists')
		    {
			    document.getElementById('showall').className = parseInt(document.getElementById('allShownValue').getText())  ? 'active' : '';
			    //var hotListEl = document.getElementById('hotList');
			    //document.getElementById('hottasks').style.display = hotListEl ? '' : 'none';
				//if (hotListEl)
				//	hotListEl.style.width = hotListEl.firstChild.clientWidth + 'px';
			    //if (tasklists.EnableLayoutCorrection)
	          	//	tasklists.CorrectLayout();

	          	applyTags();
			}


			if (callback)
			    callback();

			if (tasklists.FocusedId)
		    {
		    	var el = document.getElementById(tasklists.FocusedId);
		    	if (el)
		    		beginEdit(el);
		    }
		    tasklists.Loading = false;
		});
	}
	
	
	this.SetMode = function(el, mode)
	{
	    var items = el.parentNode.childNodes;
	    for (var i = 0; i < items.length; i++)
	        items[i].className = '';
     	el.className = 'active';

		if (this.Mode != mode)
		{
			document.getElementById('contents').innerHTML = '';
		 	this.Mode = mode;
			this.LoadTaskLists(null, tasklists.CurrentDate);
		}
	}
	
	
	this.LoadSettings = function(listId)
	{
	    var callback = function()
	    {
      		var listEl = document.getElementById(listId);
	    	tasklists.SettingsId = listId;
			core.CallApi('GetSettings&i=' + listId, function(html)
			{
				listEl.removeChild(listEl.lastChild);
				listEl.innerHTML += html;
				listEl.className += ' catN';
			});
	    };
	    
	    if (tasklists.SettingsId)
	    	tasklists.LoadTaskLists(tasklists.SettingsId != listId ? callback : null, tasklists.CurrentDate);
	    else
	    	callback();
	}

	
	this.AddTaskList = function()
	{
		core.CallApi('AddTaskList', tasklists.LoadTaskListsCallback);
	}


	this.DeleteTaskList = function(listId)
	{
		core.CallApi('DeleteTaskList&i=' + listId, tasklists.LoadTaskListsCallback);
	}


	this.DeleteTask = function(taskId)
	{
		core.CallApi('DeleteTask&i=' + taskId, tasklists.LoadTaskListsCallback);
	}
	
	
	this.SwitchTaskState = function(el, state, taskId)
	{
		var menu = document.getElementById('stateMenu');
	    if (menu == null)
	        return;

		menu.parentNode.removeChild(menu);
		el.insertBefore(menu, el.firstChild);
		menu.className = 'stateMenuVisible';
		document.onmouseup = function(e) { menu.className = ''; document.onmouseup = null; };
		
		var options = menu.getElementsByTagName('A');
		options[0].className = 'active';
		for (var i = 0; i < options.length; i++)
		{
		    var option = options[i];
		    option.onmouseover = function() { this.className = 'active'; };
			option.onmouseout = function() { this.className = ''; };
		    option.onmouseup = function(e)
		    {
				core.CallApi('SetTaskState&i=' + taskId + '&s=' + this.rel, tasklists.LoadTaskListsCallback);
				e.cancelBubble = true;
			};
		}
	}
	
	/*
	this.CorrectLayout = function()
	{
		var els = document.getElementsByTagName('DEL');
		for (var i = 0; i < els.length; i++)
		{
			var plc = els[i].parentNode;
			var dsp = plc.parentNode.style.display;
			plc.parentNode.style.display = 'block';
			plc.lastChild.style.position = 'absolute';
			plc.lastChild.style.marginRight = '-9999px';
		    plc.style.minWidth = plc.lastChild.clientWidth + 'px';
		    plc.lastChild.style.position = '';
   			plc.lastChild.style.marginRight = '';
   			plc.parentNode.style.display = dsp;
		}
	}
	*/
	
	this.SetCategory = function(listId, category)
	{
		core.CallApi('SetCategory&i=' + listId + '&c=' + category, tasklists.LoadTaskListsCallback);
	}
	
	
	this.RenewTaskList = function(listId)
	{
		core.CallApi('RenewList&i=' + listId, tasklists.LoadTaskListsCallback);
	}
	
	
	this.SetMinimized = function(listId, minimized)
	{
	    tasklists.FocusedId = minimized ? null : 'AddTask&i=' + listId;
		core.CallApi('SetMinimized&i=' + listId + '&v=' + minimized, tasklists.LoadTaskListsCallback);
	}
	
	
 	this.SetShowAll = function(el)
 	{
 	    if (el.className.indexOf('active') < 0)
 	    {
 	    	el.className = 'active';
       		core.CallApi('SetAllMinimized&v=0', tasklists.LoadTaskListsCallback);
 	    }
		else
		{
			el.className = null;
			el.onmouseup = function () { core.CallApi('SetAllMinimized&v=1', tasklists.LoadTaskListsCallback); el.onmouseup = null; };
		}
 	}
 	
 	
 	this.ShowSingleList = function(listId)
 	{
 		core.CallApi('SetAllMinimized&v=1', function() {  tasklists.SetMinimized(listId, 0) });
 	}
	
	
	this.ShiftDate = function(shift)
	{
	    var date = Date.parse(document.getElementById('datename').getText());
	    this.LoadTaskLists(null, new Date(date + shift * 24 * 3600000));
	}
	
	this.SetDate = function(dateStr)
	{
	    this.LoadTaskLists(null, new Date(dateStr));
	}

	this.SelectTimeFrame = function(el)
	{
		if (el.className.indexOf(' active') >= 0)
			el.onmouseup = function() { calendar.Close() };
		else
		{
			el.onmouseup = null;
			calendar.OnShown = function() { calendar.Element.style.marginTop = '36px'; el.className += ' active'; }
			calendar.OnClosed = function() { el.className = el.className.replace(' active', ''); }
			calendar.OnSelected = function() { tasklists.LoadTaskLists(null, calendar.SelectedDate) };
			calendar.Show(el, el.getText());
		}
	}
	
	
	this.SelectDeadline = function(el, taskId, selectedDate)
	{
		var p = document.getElementById('SetTaskTitle&i=' + taskId);
		if (p && tasklists.FocusedId != p.id)
		{
		    endEdit();
			beginEdit(p);
			el = p.parentNode.firstChild;
		}
		
		if (calendar.Element && calendar.ParentElement.id == el.id)
			el.onmouseup = function() { calendar.Close() };
		else
		{
			el.onmouseup = null;
		    if (!el.id)
		    	el.id = 'SetDeadline&i=' + taskId;

			if (!selectedDate)
			{
			    selectedDate = tasklists.CurrentDate;
			    core.CallApi(el.id + '&v=' +  parseInt(selectedDate.getTime() / 1000 - selectedDate.getTimezoneOffset() * 60), tasklists.LoadTaskListsCallback);
			}

			selectedDate = new Date(selectedDate);
			calendar.OnShown = function() { calendar.Element.style.marginTop = '26px'; calendar.Element.style.marginLeft = '-2px';}
			calendar.OnSelected = function()
			{
				var select = !selectedDate || calendar.SelectedDate.toDateString() != selectedDate.toDateString();
				selectedDate = select ? calendar.SelectedDate : null;
				core.CallApi(el.id + '&v=' + (select ? parseInt(selectedDate.getTime() / 1000 - selectedDate.getTimezoneOffset() * 60) : '0'), tasklists.LoadTaskListsCallback);
				return select;
			};
			calendar.Show(el, selectedDate, tasklists.CurrentDate);
		}

  		if (window.event)
  			window.event.cancelBubble = true;
	}
	
	
	this.SwitchHotTasks = function(el)
	{
	    var cnt = document.getElementById('contents');
		if (el.className == 'active')
			el.onmouseup = function() { cnt.className += ' noHotList'; el.className = ''; };
		else
		{
			cnt.className = cnt.className.replace(' noHotList', '');
			el.className = 'active';
			el.onmouseup = null;
		}
	}

	
	this.SwitchTag = function(el)
	{
	    var tag = '#' + el.getText();

		if (el == el.parentNode.firstChild)
			tasklists.ActiveTags = new Array();
		else if ((tasklists.ActiveTags.join('')  + '#').indexOf(tag + '#') < 0)
			tasklists.ActiveTags[tasklists.ActiveTags.length] = tag;
  		else
  		{
  			for (var i = 0; i < tasklists.ActiveTags.length; i++)
  			    if (tasklists.ActiveTags[i] == tag)
  			        tasklists.ActiveTags[i] = null;
		}
	    applyTags();
	}


	this.SetTag = function(tag)
	{
		if ((tasklists.ActiveTags.join('')  + '#').indexOf(tag + '#') < 0)
			tasklists.ActiveTags[tasklists.ActiveTags.length] = tag;
		applyTags();
	}


	function applyTags()
	{
	    var activeTags = tasklists.ActiveTags;
	    var tagsStr = activeTags.join('') + '#';
	    
	    // setting the class to buttons
	    var tagBar = document.getElementById('tagBar');
	    if (tagBar)
	    {
		    var nodes = tagBar.getElementsByTagName('A');
		    nodes[0].className = tagsStr.length > 1 ? nodes[0].className.replace(/ active/g, '') : nodes[0].className + ' active';
		    for (var i = 1; i < nodes.length; i++)
		    {
		        if (tagsStr.indexOf('#' + nodes[i].getText() + '#') < 0)
		        	nodes[i].className = nodes[i].className.replace(/ active/g, '');
				else if (nodes[i].className.indexOf(' active') < 0)
					nodes[i].className += ' active';
			}
	    }
	    
	    // showing and hiding items
	    var tasks = document.getElementsByTagName('INS');
	    if (tagsStr.length <= 1)
	    {
    		for (var j = 0; j < tasks.length; j++)
    			tasks[j].parentNode.style.display = '';
    	}
	    else
	    {
	    	for (var j = 0; j < tasks.length; j++)
	    	{
	    	    var task = tasks[j];
	    	    task.parentNode.style.display = 'none';
				for (var i = 0; i < activeTags.length; i++)
				{
					if (task.className.indexOf(activeTags[i]) >= 0)
					{
						task.parentNode.style.display =  '';
					    break;
					}
				}
			}
	    }
	}
	
	
	function beginEdit(el)
	{
		el.className += ' editing';
		el.parentNode.className += ' selectedTask';
		tasklists.FocusedId = el.id;
		tasklists.DefaultText = el.getText();

		if (el.className.indexOf('empty') >= 0)
		    el.innerHTML = '&#8203;';
  		else
  		{
  		    var els = el.getElementsByTagName('A');
  		    for (var i = 0; i < els.length; i++)
  		    {
  		        els[i].setText(els[i].href.replace('mailto:', ''));
  		        tasklists.DefaultText = null;
  		    }
  		}

		el.onkeydown = checkKey;
	    el.contentEditable = true;
	    el.focus();
	    core.ScrollIntoView(el);
	}
	
	
	function endEdit()
	{
	    if (tasklists.FocusedId == null)
	        return;

		var el = document.getElementById(tasklists.FocusedId);
		if (el != null)
		{
			var text = el.getText().replace('\u200b', '').replace(/^\s+|\s+$/g, '');
			if (!text.length && el.className.indexOf('empty') >= 0)
				text = el.setText(tasklists.DefaultText);

			if (text != tasklists.DefaultText)
			{
				var val = el.innerHTML.replace(/&nbsp;|<br>/g, ' ').replace(/&/g, '%26');
			    core.CallApi(el.id + '&v=' + val + '&s=' + parseInt(tasklists.CurrentDate.getTime() / 1000 - tasklists.CurrentDate.getTimezoneOffset() * 60), tasklists.LoadTaskListsCallback);
			}
			else
			    tasklists.FocusedId = null;

			el.className = el.className.replace(' editing', '');
			el.parentNode.className = el.parentNode.className.replace(' selectedTask', '');
			el.contentEditable = false;
			el.blur();
		}
		tasklists.DefaultText = null;
	}
	
	
	function checkKey(e)
	{
        e = window.event ? window.event : e;
        var el = e.srcElement ? e.srcElement : e.target;
        
        switch (e.keyCode ? e.keyCode : e.which) {
            case 9: // tab
                endEdit();
                break;

            case 13: // enter
                endEdit();
                return e.keyCode ? e.keyCode = 27 : e.which = 27;
                break;

            case 27: // esc
				tasklists.LoadTaskLists(null, tasklists.CurrentDate);
				tasklists.FocusedId = null;
                break;
        }
	}
	
	
	function pickListUp(el, x, y)
	{
		document.onmousemove = adjustListLocation;
	    document.onmouseup = commitListLocation;

        tasklists.MovingEl = el.parentNode;
        tasklists.MovingEl.parentNode.style.width = tasklists.MovingEl.parentNode.offsetWidth + 'px';
        tasklists.MovingEl.className += ' moving';
        tasklists.MovingEl.pageLoc = [x, y];
        tasklists.MovingEl.style.marginTop = '32px';
        tasklists.MovingEl.parentNode.style.height = tasklists.MovingEl.offsetHeight + 'px';
        tasklists.MovingEl.parentNode.parentNode.className += ' moveTarget';
	}
	
	
	function adjustListLocation(e)
	{
	    if (tasklists.MovingEl == null)
	    	return;

		e = window.event ? window.event : e;
		var x = e.clientX ? e.clientX : e.x;
        var y = e.clientY ? e.clientY : e.y;
     	tasklists.MovingEl.style.marginLeft = (x - tasklists.MovingEl.pageLoc[0]) + 'px';
     	tasklists.MovingEl.style.marginTop = (y - tasklists.MovingEl.pageLoc[1] + 32) + 'px';
	}
	
	
	function commitListLocation(e)
	{
	    if (!tasklists.MovingEl)
	    	return;
	    	
		var el = core.GetEl(e);
		var p = core.GetParentByClassName(el, 'placeholder');
		var m = tasklists.MovingEl;
		var board = m.parentNode.parentNode;
		
		board.className = board.className.replace(' moveTarget', '');
		m.style.marginLeft = null;
		m.style.marginTop = null;
		m.className = m.className.replace(' moving', '');
		tasklists.MovingEl = null;
  		
  		if (p)
		{

			var srcIdx = getNodeIndex(p.parentNode.childNodes, m.parentNode);
      var dstIdx = getNodeIndex(p.parentNode.childNodes, p);

		    p.parentNode.insertBefore(m.parentNode, dstIdx > srcIdx || p.id == 'hotList' ? p.nextSibling : p);
				core.CallApi('SetListOrder&l=' + m.id + '&i=' + (p.parentNode.childNodes[0].id == 'hotList' ? dstIdx - 1 : dstIdx), tasklists.LoadTaskListsCallback);
		}
	}


	function getNodeIndex(childNodes, node)
	{
  	for (var i = 0; i < childNodes.length; i++)
			if (childNodes[i] == node)
				return i;
		return -1;
	}


	this.AssignEmail = function(el, account)
	{
    	var val = document.getElementById('assignmentEmail').value.trim();
		if (!val.length)
        	tasklists.ClosePopup(el);
		else
		{
	  		core.CallApi('AssignEmail&v=' + val, function(html) { document.getElementById('contents').innerHTML = ''; tasklists.LoadTaskListsCallback(html); });
        }
	}


	this.RecoverAccount = function(el)
	{
    	var val = document.getElementById('recoveryEmail').value.trim();
		if (!val.length)
        	tasklists.ClosePopup(el);
		else
		{
	  		core.CallApi('RecoverAccount&v=' + val, function(html) { document.getElementById('contents').innerHTML = ''; tasklists.LoadTaskListsCallback(html); });
        }
	}
}