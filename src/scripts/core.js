var core = new core();

function core()
{
	this.Account = null;

	this.CallApi = function(method, callback)
	{
		document.body.className = 'working';
		
		var httpRequest = window.XMLHttpRequest != null ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHttp");
        httpRequest.open('POST', 'apis/Ajax.Api.php', true);
        httpRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        httpRequest.onreadystatechange = function() {
            if (httpRequest.readyState == 4) {
            	document.body.className = '';
                if (httpRequest.status == 200 && callback)
                    callback(httpRequest.responseText);
           }
        };
        httpRequest.send('m=' + method + '&a=' + core.Account);
	}


	this.GetParentNode = function(node, tagName)
	{
		while (node != null && node.tagName != tagName)
			node = node.parentNode;
		return node;
	};


	this.CatchEvent = function(e)
	{
        calendar.Close();
		e = window.event ? window.event : e
		e.cancelBubble = true;
		if (e.stopPropagation)
			e.stopPropagation();
		return e;
	}
	   
    
    this.GetParentByClassName = function(el, className)
	{
	    if (el != null && (el.className == null || el.className.indexOf(className) < 0))
	        return core.GetParentByClassName(el.parentNode, className);
	    return el;
	}
	

	this.GetEl = function(e)
	{
		e = window.event ? window.event : e;
	    return e.srcElement ? e.srcElement : e.target;
	}

	
	this.ScrollIntoView = function(el)
	{
	    var top = parseInt(el.style.top);
		if (!top)
			return;

		var bottom = top + el.clientHeight;
		var windowHeight = document.documentElement.clientHeight === 0 ? document.body.offsetHeight : document.documentElement.clientHeight;

		var scrollTop = 0;
		if (window.innerHeight)
            scrollTop = window.pageYOffset; // FF
        else if (document.documentElement && document.documentElement.scrollTop)
            scrollTop = document.documentElement.scrollTop; // IE
        else if (document.body)
            scrollTop = document.body.scrollTop;

		var targetScroll = bottom - windowHeight + 32;
		if (targetScroll > scrollTop)
			window.scrollBy(0, targetScroll - scrollTop);
	}
	
	
	this.InvokeMailto = function(recipient)
	{
		window.location.href = 'mailto:' + (recipient ? recipient : 'info') + String.fromCharCode(64) + 'dreamsplan.com';
	}


    this.SelectContents = function(el)
	{
        var range;
        if (document.selection)
		{
            if (range = document.body.createTextRange())
			{
                range.moveToElementText(el);
                range.select();
            }
        }
        else if (window.getSelection)
		{
            if (range = document.createRange())
			{
                range.selectNode(el);
                window.getSelection().addRange(range);
            }
        }
    };
}