"use strict";!function(e,t,i){function n(t,i){var n;if(":"===i.charAt(0)){var s=i.match(/^:[a-z]+/);s&&(i=i.replace(/^:[a-z]+ /,""),s=s[0],n=new RegExp(e.ui.autocomplete.escapeRegex(s),"i"),t=e.grep(t,function(e){return n.test(e.command)}))}n=new RegExp(e.ui.autocomplete.escapeRegex(i),"i");var o=[],a=e.grep(t,function(e){return n.test(e.label)}),l=e.grep(t,function(e){return n.test(e.value)}),u=e.grep(t,function(e){return n.test(e.tags)});return o=o.concat(a).concat(l).concat(u),o.filter(function(e,t,i){return t===i.indexOf(e)})}var s=e.ui.autocomplete.prototype,o=s._initSource;e.extend(s,{_initSource:function(){e.isArray(this.options.source)?this.source=function(e,t){t(n(this.options.source,e.term))}:o.call(this)}}),t.behaviors.valet={attach:function(i){e("#valet",i).once("valet").each(function(){var i=new t.valet.models.ValetModel;new t.valet.views.ValetView({el:e(this),model:i})})}},t.valet=t.valet||{models:{},views:{}},t.valet.models.ValetModel=Backbone.Model.extend({defaults:{isOpen:!1}}),t.valet.views.ValetView=Backbone.View.extend({initialize:function(){var t=this;this.$input=this.$el.find(".valet-input"),this.$window=e(window),this.$body=e("body"),this.$results=e("#valet-results"),this.down=[],this.$el.find(".valet-close").click(this.toggle.bind(this)),this.$el.find(".valet-open-link").click(function(e){e.preventDefault(),t.toggle()}),this.timeout=null,_.bindAll(this,"keyDown"),_.bindAll(this,"keyUp"),e(document).bind("keydown",this.keyDown).bind("keyup",this.keyUp),e(".valet-trigger").click(function(e){e.preventDefault(),t.toggle()}),this.$input.autocomplete({appendTo:this.$results,sortResults:!1,minLength:1,delay:0,autoFocus:!0,search:function(e,i){t.disableMouse(),t.$results.addClass("searching")},response:function(e,i){clearTimeout(t.timeout),t.timeout=setTimeout(function(){t.$results.removeClass("searching")},10)},focus:function(t,i){return e(".ui-state-focus",t.currentTarget).removeClass("ui-state-focus"),e(".ui-state-active",t.currentTarget).closest("li").addClass("ui-state-focus"),!1},select:function(e,i){if(i.item)return t.go(i.item.value),!1}}),this.$input.autocomplete("instance")._renderItem=function(t,i){var n=i.value.length>0?i.value:"/";n=i.value.length>85?i.value.substring(0,85)+"...":n;var s=i.icon?'<i class="'+i.icon+'"></i>':"";return e("<li></li>").append("<a>"+s+"<strong>"+i.label+"</strong><em>"+i.description+"</em><small>"+n+"</small></a>").appendTo(t)},this.$input.autocomplete("instance")._renderMenu=function(t,i){var n=this;i=i.slice(0,6),e.each(i,function(e,i){n._renderItemData(t,i)})}},toggle:function(){var t=this;this.model.get("isOpen")?(this.enableMouse(),this.$el.removeClass("open"),this.$body.removeClass("valet-open"),this.model.set("isOpen",!1),""!==this.$input[0].value&&setTimeout(function(){t.$el.addClass("hideInput"),setTimeout(function(){t.$el.removeClass("hideInput"),t.$input[0].value=""},300)},500),this.$input[0].blur(),this.$window.off("click.valet")):(this.disableMouse(),this.getData(this.autoComplete.bind(this)),this.$el.addClass("open"),this.$body.addClass("valet-open"),this.model.set("isOpen",!0),this.$input.val(""),this.$input[0].focus(),setTimeout(function(){t.$window.on("click.valet",function(i){e(i.target).closest(".valet-inner").length||t.toggle()})},300))},autoComplete:function(e){this.$input.autocomplete("option",{source:e})},go:function(e){e=e.replace("RETURN_URL",window.location.pathname.substring(1)),this.down[16]?(this.down[16]=!1,this.toggle(),window.open(e)):(this.$input.val("Loading...").attr("disabled",!0),window.location=e)},getData:function(t){var n=this,s=localStorage?JSON.parse(localStorage.getItem("valet")):null;if(s&&i.valet.cache&&s.timestamp>=i.valet.cache)return t(s.data);n.$input.val("Loading data...").attr("disabled",!0),e.ajax({url:i.path.baseUrl+"api/valet",dataType:"json",success:function(e){if(n.$input.val("").attr("disabled",!1),n.$input[0].focus(),localStorage){var i=Math.floor((new Date).getTime()/1e3);localStorage.setItem("valet",JSON.stringify({timestamp:i,data:e}))}return t(e)},error:function(e,i,n){return alert(e.status),alert(n),t(null)}})},keyDown:function(t){if(this.model.get("isOpen")&&27===t.keyCode)return void this.toggle();this.down[t.keyCode]=!0,this.down[i.valet.hotkey]&&this.down[i.valet.modifier]&&!e(t.target).is(":focus")&&(t.preventDefault(),this.toggle())},keyUp:function(e){this.down[e.keyCode]=!1},enableMouse:function(){this.$results.removeClass("valet-mouse-disabled"),this.$window.off("mousemove.valet")},disableMouse:function(){var e=this;this.$results.addClass("valet-mouse-disabled"),this.$window.on("mousemove.valet",function(){e.enableMouse()})}})}(jQuery,Drupal,drupalSettings);