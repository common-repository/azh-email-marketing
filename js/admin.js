(function($) {
    "use strict";
    var $window = $(window);
    var $body = $('body');
    var $document = $(document);
    function parse_query_string(a) {
        if (a === "")
            return {};
        var b = {};
        for (var i = 0; i < a.length; ++i)
        {
            var p = a[i].split('=');
            if (p.length !== 2)
                continue;
            b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
        }
        return b;
    }
    $.QueryString = parse_query_string(window.location.search.substr(1).split('&'));
    if ('page' in $.QueryString && $.QueryString['page'] === 'azh-email-templates-settings') {
        $('.aze-email-template-upload').on('click', function(e) {
            e.preventDefault();

            var $input = $('#aze-email-template-upload');
            $input.off('change').on('change', function() {
                var file = $input.get(0).files[0];
                var xhr = new XMLHttpRequest();
                if (xhr.upload) {
                    xhr.upload.addEventListener("progress", function(e) {
                        $('.aze-progress .aze-status').width((e.loaded / e.total * 100) + '%');
                    }, false);
                    xhr.onreadystatechange = function(e) {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) {
                                $('.aze-progress .aze-status').width('0%');
                                $input.off('change');
                                $input.val('');
                                if (xhr.response) {
                                    var template = JSON.parse(xhr.response);
                                    var $iframe = $('<iframe src="' + template.url + '"></iframe>').appendTo($body).on('load', function() {
                                        function process() {
                                            function cropped_screenshot(element, options) {
                                                options = options || {};
                                                // our cropping context
                                                var cropper = iframe_document.createElement('canvas').getContext('2d');
                                                // save the passed width and height
                                                var finalWidth = options.width || iframe_window.innerWidth;
                                                var finalHeight = options.height || iframe_window.innerHeight;
                                                // update the options value so we can pass it to h2c
                                                if (options.x) {
                                                    options.width = finalWidth + options.x;
                                                }
                                                if (options.y) {
                                                    options.height = finalHeight + options.y;
                                                }
                                                var userCallback = options.onrendered;
                                                // wrap the passed callback in our own
                                                options.onrendered = function(canvas) {
                                                    cropper.canvas.width = finalWidth;
                                                    cropper.canvas.height = finalHeight;
                                                    cropper.drawImage(canvas, -(+options.x || 0), -(+options.y || 0));
                                                    if (typeof userCallback === 'function') {
                                                        userCallback(cropper.canvas);
                                                    }
                                                };
                                                iframe_window.html2canvas(element, options);
                                            }
                                            if (loaded === 2) {
                                                (function($) {
                                                    function upload_section(i, callback) {
                                                        window.jQuery('.aze-progress .aze-status').width(Math.round((i / sections.length) * 100) + '%');
                                                        if (i < sections.length) {
                                                            iframe_window.html2canvas(sections[i], {
                                                                width: 800
                                                            }).then(function(canvas) {
                                                                function toAbsoluteURL(url) {
                                                                    if (url.search(/^\/\//) != -1) {
                                                                        return iframe_window.location.protocol + url
                                                                    }
                                                                    if (url.search(/:\/\//) != -1) {
                                                                        return url
                                                                    }
                                                                    if (url.search(/^\//) != -1) {
                                                                        return iframe_window.location.origin + url
                                                                    }
                                                                    var base = iframe_window.location.href.match(/(.*\/)/)[0]
                                                                    return base + url;
                                                                }
                                                                $(sections[i]).find('img[src]').each(function() {
                                                                    $(this).attr('src', toAbsoluteURL($(this).attr('src')));
                                                                });
                                                                $(sections[i]).find('[background]').each(function() {
                                                                    $(this).attr('background', toAbsoluteURL($(this).attr('background')));
                                                                });
                                                                $(sections[i]).find('[style*="background-image"]').each(function() {
                                                                    var style = $(this).attr('style').replace(/background-image[: ]*url\([\'\" ]*([^\)\'\"]*)[\'\" ]*\) *;/, function(match, url) {
                                                                        return match.replace(url, encodeURI(toAbsoluteURL(decodeURI(url))));
                                                                    });
                                                                    $(this).attr('style', style);
                                                                });
                                                                $.post(aze.ajaxurl, {
                                                                    action: 'aze_upload_section',
                                                                    template: template.name,
                                                                    name: i,
                                                                    html: $('<div>').append($(sections[i]).clone()).html(),
                                                                    preview: canvas.toDataURL("image/jpeg", 0.95)
                                                                }, function(data) {
                                                                    upload_section(i + 1, function() {
                                                                        callback();
                                                                    });
                                                                });
                                                            });
                                                        } else {
                                                            window.jQuery('.aze-progress').fadeOut("slow");
                                                            callback();
                                                        }
                                                    }
                                                    cropped_screenshot(iframe_document.body, {
                                                        x: 0,
                                                        y: 0,
                                                        width: 800,
                                                        height: 500,
                                                        onrendered: function(canvas) {
                                                            var styles = $('style').toArray().map(function(element) {
                                                                return $(element).html();
                                                            }).join("\n");
                                                            var stylesheets = $('link[type="text/css"]').toArray().map(function(element) {
                                                                return $('<div>').append($(element).clone()).html();
                                                            }).join("\n");
                                                            var $head = $('head').clone();
                                                            $head.find('style').detach();
                                                            $head.find('link[type="text/css"]').detach();
                                                            $.post(aze.ajaxurl, {
                                                                action: 'aze_upload_section',
                                                                template: template.name,
                                                                name: 'index',
                                                                styles: styles,
                                                                stylesheets: stylesheets,
                                                                preview: canvas.toDataURL("image/jpeg", 0.95)
                                                            }, function(data) {
                                                            });
                                                        },
                                                        useCORS: true
                                                    });
                                                    var sections = $('body > div, body > table').toArray();
                                                    upload_section(0, function() {
                                                        alert(aze.i18n.done);
                                                    });
                                                })(iframe_window.jQuery);
                                            }
                                        }
                                        var iframe_body = $iframe.contents().find('body').get(0);
                                        var iframe_window = $iframe.get(0).contentWindow;
                                        var iframe_document = $iframe.get(0).contentDocument || $iframe.contentWindow.document;
                                        var loaded = 0;
                                        var jquery = iframe_document.createElement('script');
                                        jquery.type = 'text/javascript';
                                        jquery.src = aze.jquery;
                                        iframe_document.body.appendChild(jquery);
                                        jquery.onload = function() {
                                            loaded++;
                                            process();
                                        };
                                        var html2canvas = iframe_document.createElement('script');
                                        html2canvas.type = 'text/javascript';
                                        html2canvas.src = aze.html2canvas;
                                        iframe_document.body.appendChild(html2canvas);
                                        html2canvas.onload = function() {
                                            loaded++;
                                            process();
                                        };
                                    });
                                    $iframe.css({
                                        'width': '800px',
                                        'height': '500px',
                                        'position': 'absolute',
                                        'visibility': 'hidden'
                                    });
                                }
                            }
                        }
                    };
                    xhr.open("POST", aze.ajaxurl + '?action=aze_upload_template&format=stampready', true);
                    xhr.setRequestHeader("X-FILENAME", file.name);
                    xhr.send(file);
                    $('.aze-progress .aze-status').width('0%');
                    $('.aze-progress').fadeIn("slow");
                }
            });
            $input.trigger('click');
        });
    }
    $(function() {
        function refresh_tokens() {
            var form_title = $form_title.val();
            var tokens = [];
            $email_field.children('[data-form-title="' + form_title + '"]').each(function() {
                tokens.push('<input type="text" value="{' + $(this).attr('value') + '}"/>');
            });
            $('.aze-tokens').html(tokens.join(' '));
            $('.aze-tokens input').each(function(){
                $(this).attr('size', $(this).val().length);
            });    
        }
        var $email_field = $('[name="_email_field"]');
        var $form_title = $('[name="_form_title"]').on('change', function(event) {
            var form_title = $(this).val();
            $email_field.children().show().not('[data-form-title="' + form_title + '"]').hide();
            if ($email_field.children('[data-form-title="' + form_title + '"]').filter('[value="email"]').length) {
                $email_field.val('email');
            } else {
                $email_field.val($email_field.children('[data-form-title="' + form_title + '"]').first().attr('value'));
            }
            refresh_tokens();
        });
        if(!$form_title.children('[selected]').length) {
            $form_title.trigger('change');
        } else {
            refresh_tokens();
        }        
        $('.aze-pause').on('click', function() {
            var $status = $(this).closest('[data-campaign]');
            var id = $status.data('campaign');
            $.post(aze.ajaxurl, {
                action: 'aze_pause_campaign',
                campaign: id
            }, function(status) {
                $status.attr('data-status', status);
            });
        });
        $('.aze-run').on('click', function() {
            var $status = $(this).closest('[data-campaign]');
            var id = $status.data('campaign');
            $.post(aze.ajaxurl, {
                action: 'aze_run_campaign',
                campaign: id
            }, function(status) {
                $status.attr('data-status', status);
            });
        });
    });
})(window.jQuery);


