/* =================================================================
                              MODULE NAME
====================================================================  */

var myRevealingModule = (function () {
  "use strict";

  var privateCounter = 0;

  var config = {
    foo: "bar"
  };

  function privateFunction() {
      privateCounter++;
      console.log(config.foo);
  }

  function publicFunction() {

     $("input").focus(function() {
        var inputID = $(this).attr('id');
        $("label[for=" + inputID + "]").addClass("curFocus");
      });

     $("input").blur(function() {
        $(this).parent().removeClass("curFocus")
      });

      publicIncrement();
  }

  function publicIncrement() {
      privateFunction();
  }

  function publicGetCount(){
    return privateCounter;
  }

  // Reveal public pointers to
  // private functions and properties
 return {
      start: publicFunction,
      increment: publicIncrement,
      count: publicGetCount
  };
 
})();
 
 $(document).ready(function(){
  myRevealingModule.start();
});
