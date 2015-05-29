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
 
myRevealingModule.start();