YUI(M.yui.loader).use('io-base', 'node', function(Y) {
    Y.all(".overview-loading").show(true);
    var handleSuccess = function (transactionid, response, arguments) {
	    var data = response.responseText;
	    Y.one('#region-main .content').setContent(data);
	    Y.all("div.course-overview").hide(true);
	    Y.on('click', function(e) {
	  		    e.preventDefault();
	  		    var node = Y.one("#" + /^(.*)-link/.exec(this.get('id'))[1]);
	  		    node.toggleView().siblings("div.course-overview").hide(true);
	  		}, 'a.overview-link');
	};

  	var handleFailure = function (transactionid, response, arguments) {
  		Y.all("img.overview-loading").hide(true);
  	};

  	var cfg =  {
  	    method: 'POST',
  	    data: { overview: 1, time: new Date().getTime() },
  	    on: {
  	  	    success:handleSuccess,
  	  	    failure:handleFailure
  	    }
  	};
  	var request = Y.io(M.cfg.wwwroot + '/blocks/mycourses/lib.php', cfg);
});