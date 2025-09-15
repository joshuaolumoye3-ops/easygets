(function ($) {
  'use strict';
  $.contextMenu({
    selector: '#context-menu-simple',
    callback: function (key, options) { },
    items: {
      "edit": { name: "Edit" },
      "cut": { name: "Cut" },
      copy: { name: "Copy" },
      "paste": { name: "Paste" },
      "delete": { name: "Delete" },
      "sep1": "---------",
      "quit": { name: "Quit" }
    }
  });

  $.contextMenu({
    selector: '#context-menu-access',
    callback: function (key, options) {
      var m = "clicked: " + key;
      window.console && console.log(m) || alert(m);
    },
    items: {
      "edit": { name: "Edit", accesskey: "e" },
      "cut": { name: "Cut", accesskey: "c" },
      "copy": { name: "Copy", accesskey: "c o p y" },
      "paste": { name: "Paste", accesskey: "cool paste" },
      "delete": { name: "Delete" },
      "sep1": "---------",
      "quit": { name: "Quit" }
    }
  });

  $.contextMenu({
    selector: '#context-menu-open',
    callback: function (key, options) {
      var m = "clicked: " + key;
      window.console && console.log(m) || alert(m);
    },
    items: {
      "edit": { name: "Closing on Click", callback: function () { return true; } },
      "cut": { name: "Open after Click", callback: function () { return false; } }
    }
  });

  $.contextMenu({
    selector: '#context-menu-multi',
    callback: function (key, options) {
      var m = "clicked: " + key;
      window.console && console.log(m) || alert(m);
    },
    items: {
      "edit": { name: "Edit" },
      "cut": { name: "Cut" },
      "sep1": "---------",
      "quit": { name: "Quit" },
      "sep2": "---------",
      "fold1": {
        name: "Sub group",
        items: {
          "fold1-key1": { name: "Foo bar" },
          "fold2": {
            name: "Sub group 2",
            items: {
              "fold2-key1": { name: "alpha" },
              "fold2-key2": { name: "bravo" },
              "fold2-key3": { name: "charlie" }
            }
          },
          "fold1-key3": { name: "delta" }
        }
      },
      "fold1a": {
        name: "Other group",
        items: {
          "fold1a-key1": { name: "echo" },
          "fold1a-key2": { name: "foxtrot" },
          "fold1a-key3": { name: "golf" }
        }
      }
    }
  });

  $.contextMenu({
    selector: '#context-menu-hover',
    trigger: 'hover',
    delay: 500,
    callback: function (key, options) {
      var m = "clicked: " + key;
      window.console && console.log(m) || alert(m);
    },
    items: {
      "edit": { name: "Edit" },
      "cut": { name: "Cut" },
      "copy": { name: "Copy" },
      "paste": { name: "Paste" },
      "delete": { name: "Delete" },
      "sep1": "---------",
      "quit": { name: "Quit" }
    }
  });

  $.contextMenu({
    selector: '#context-menu-hover-autohide',
    trigger: 'hover',
    delay: 500,
    autoHide: true,
    callback: function (key, options) {
      var m = "clicked: " + key;
      window.console && console.log(m) || alert(m);
    },
    items: {
      "edit": { name: "Edit" },
      "cut": { name: "Cut" },
      "copy": { name: "Copy" },
      "paste": { name: "Paste" },
      "delete": { name: "Delete" },
      "sep1": "---------",
      "quit": { name: "Quit" }
    }
  });
})(jQuery);
