YUI(M.yui.loader).use('io-base', 'node', 'json-parse', function(Y) {
    Y.all(".overview-loading").show(true);
    var handleSuccess = function (transactionid, response, arguments) {

        var rawdata = response.responseText;

        if (rawdata) {
            var data = Y.JSON.parse(rawdata);
            if (data.html){
                Y.one('#region-main .block_mycourses .content').setContent(data.html);
                Y.all("div.course-overview").hide(true);
                Y.on('click', function(e) {
                        e.preventDefault();
                        var node = Y.one("#" + /^(.*)-link/.exec(this.get('id'))[1]);
                        node.toggleView().siblings("div.course-overview").hide(true);
                    }, 'a.overview-link');
            } else {
                Y.all("img.overview-loading").hide(true);
                var str = M.util.get_string('overload', 'block_mycourses');
                var node = Y.Node.create('<div class="block_mycourses_overload">'+ str +'</div>');
                Y.one('#region-main .block_mycourses .header').insert(node);
            }
        } else {
            Y.all("img.overview-loading").hide(true);
        }
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

    Y.on('contentready', function() {
        this.addClass('load');
    }, '#region-main .block_mycourses .block_mycourses_overload');
});