(function($) {
    "use strict";
    function font_context(selector) {
        azh.controls_options = azh.controls_options.concat([
            {
                "type": "font-family",
                "menu": "context",
                "group": "Font style",
                "property": "font-family",
                "selector": selector,
                "control_class": "azh-font-family",
                "control_type": "font-family",
                "control_text": "Font family"
            },
            {
                "type": "integer-style",
                "menu": "context",
                "group": "Font style",
                "property": "font-size",
                "selector": selector,
                "responsive": true,
                "slider": true,
                "units": {
                    "px": {
                        "min": "1",
                        "max": "200",
                        "step": "1"
                    },
                    "em": {
                        "min": "0.1",
                        "max": "10",
                        "step": "0.1"
                    },
                    "rem": {
                        "min": "0.1",
                        "max": "10",
                        "step": "0.1"
                    }
                },
                "control_class": "azh-integer",
                "control_type": "font-size",
                "control_text": "Font size"
            },
            {
                "type": "dropdown-style",
                "menu": "context",
                "group": "Font style",
                "property": "font-weight",
                "selector": selector,
                "options": {
                    "100": "100",
                    "200": "200",
                    "300": "300",
                    "400": "400",
                    "500": "500",
                    "600": "600",
                    "700": "700",
                    "800": "800",
                    "900": "900"
                },
                "control_class": "azh-dropdown",
                "control_type": "font-weight",
                "control_text": "Font weight"
            },
            {
                "type": "dropdown-style",
                "menu": "context",
                "group": "Font style",
                "property": "font-style",
                "selector": selector,
                "options": {
                    "": "Default",
                    "normal": "Normal",
                    "italic": "Italic",
                    "oblique": "Oblique"
                },
                "control_class": "azh-dropdown",
                "control_type": "font-style",
                "control_text": "Font style"
            },
            {
                "type": "dropdown-style",
                "menu": "context",
                "group": "Font style",
                "property": "text-transform",
                "selector": selector,
                "options": {
                    "": "Default",
                    "uppercase": "Uppercase",
                    "lowercase": "Lowercase",
                    "capitalize": "Capitalize",
                    "none": "Normal"
                },
                "control_class": "azh-dropdown",
                "control_type": "text-transform",
                "control_text": "Transform"
            },
            {
                "type": "color-style",
                "menu": "context",
                "group": "Font style",
                "property": "color",
                "selector": selector,
                "control_class": "azh-color",
                "control_type": "color",
                "control_text": "Color"
            }
        ]);
    }
    function text_context(selector) {
        azh.controls_options = azh.controls_options.concat([
            {
                "type": "integer-style",
                "menu": "context",
                "group": "Text style",
                "property": "line-height",
                "selector": selector,
                "responsive": true,
                "slider": true,
                "units": {
                    "px": {
                        "min": "1",
                        "max": "100",
                        "step": "1"
                    },
                    "%": {
                        "min": "1",
                        "max": "300",
                        "step": "1"
                    },
                    "em": {
                        "min": "0.1",
                        "max": "10",
                        "step": "0.1"
                    }
                },
                "control_class": "azh-integer",
                "control_type": "line-height",
                "control_text": "Line height"
            },
            {
                "type": "radio-style",
                "menu": "context",
                "group": "Text style",
                "property": "text-align",
                "selector": selector,
                "responsive": true,
                "options": {
                    "left": "Left",
                    "center": "Center",
                    "right": "Right",
                    "justify": "Justify",
                },
                "control_class": "azh-text-align",
                "control_type": "text-align",
                "control_text": "Text align"
            },
            {
                "type": "integer-style",
                "menu": "context",
                "selector": selector,
                "group": "Text style",
                "responsive": true,
                "property": "word-spacing",
                "min": "-20",
                "max": "50",
                "step": "1",
                "units": "px",
                "control_class": "azh-integer",
                "control_type": "word-spacing",
                "control_text": "Word-spacing"
            },
            {
                "type": "integer-style",
                "menu": "context",
                "group": "Text style",
                "property": "letter-spacing",
                "selector": selector,
                "responsive": true,
                "min": "-5",
                "max": "10",
                "step": "0.1",
                "units": "px",
                "control_class": "azh-integer",
                "control_type": "letter-spacing",
                "control_text": "Letter-spacing"
            }
        ]);
    }

    window.azh = $.extend({}, window.azh);
    if (!('controls_options' in azh)) {
        azh.controls_options = [];
    }
    if (!('modal_options' in azh)) {
        azh.modal_options = [];
    }
    azh.controls_options = azh.controls_options.concat([
//        {
//            "order": 0,
//            "type": "url-attribute",
//            "menu": "context",
//            "attribute": "href",
//            "control_class": "azh-link",
//            "control_type": "link",
//            "control_text": "Edit link",
//            "selector": 'a[href]'
//        },
        {
            "type": "integer-attribute",
            "menu": "context",
            "attribute": "width",
            "selector": "[width$='%']",
            "units": "%",
            "min": "0",
            "max": "100",
            "step": "1",
            "control_class": "azh-integer",
            "control_type": "width",
            "control_text": "Width (%)"
        },
        {
            "type": "integer-attribute",
            "menu": "context",
            "attribute": "height",
            "selector": "[height$='px']",
            "units": "px",
            "control_class": "azh-integer",
            "control_type": "height",
            "control_text": "Height (px)"
        },
        {
            "type": "color-style",
            "menu": "context",
            "selector": "[data-bgcolor][style*='background-color']",
            "property": "background-color",
            "control_class": "azh-color",
            "control_type": "background-color",
            "control_text_attribute": "data-bgcolor"
        },
        {
            "type": "color-style",
            "menu": "context",
            "selector": "[data-bgcolor][style*='background']",
            "property": "background-color",
            "control_class": "azh-color",
            "control_type": "background-color",
            "control_text_attribute": "data-bgcolor"
        },
        {
            "type": "color-attribute",
            "menu": "context",
            "selector": "[data-bgcolor]",
            "attribute": "bgcolor",
            "control_class": "azh-color",
            "control_type": "background-color",
            "control_text_attribute": "data-bgcolor"
        },
        {
            "type": "background-image",
            "menu": "context",
            "selector": "[data-bg][style*='background']",
            "control_class": "azh-image",
            "control_type": "background-image",
            "control_text_attribute": "data-bg"
        },
        {
            "type": "image-attribute",
            "menu": "context",
            "selector": "[data-bg][background]",
            "attribute": "background",
            "control_class": "azh-image",
            "control_type": "background-image",
            "control_text_attribute": "data-bg"
        },
        {
            "type": "color-style",
            "menu": "context",
            "selector": "[data-border-top-color]",
            "property": "border-top-color",
            "control_class": "azh-color",
            "control_type": "border-top-color",
            "control_text_attribute": "data-border-top-color"
        },
        {
            "type": "color-style",
            "menu": "context",
            "selector": "[data-border-left-color]",
            "property": "border-left-color",
            "control_class": "azh-color",
            "control_type": "border-left-color",
            "control_text_attribute": "data-border-left-color"
        },
        {
            "type": "color-style",
            "menu": "context",
            "selector": "[data-border-right-color]",
            "property": "border-right-color",
            "control_class": "azh-color",
            "control_type": "border-right-color",
            "control_text_attribute": "data-border-right-color"
        },
        {
            "type": "color-style",
            "menu": "context",
            "selector": "[data-border-bottom-color]",
            "property": "border-bottom-color",
            "control_class": "azh-color",
            "control_type": "border-bottom-color",
            "control_text_attribute": "data-border-bottom-color"
        },
        {
            "type": "background-image",
            "menu": "context",
            "property": "background-image",
            "control_class": "azh-image",
            "control_type": "background-image",
            "control_text": "Background image"
        }
    ]);
    font_context('[data-color], [data-size], [data-min], [data-max], [data-link-color], [data-link-size], [data-link-style]');
    //font_context('[contenteditable]');
    text_context('[data-color], [data-size], [data-min], [data-max], [data-link-color], [data-link-size], [data-link-style]');
    //text_context('[contenteditable]');
})(window.jQuery);