var nav = null;

function init_dev_steps(step) {
  this.step = step;
  this.moving = false;
  
  this.steps_glass = document.getElementById('dev_steps_s');
  this.steps_glass_c = this.steps_glass.querySelector('.content');
  this.steps_wrap = document.getElementById('dev_steps_c');
  this.steps_content = this.steps_wrap.querySelector('.content');

  this.easeInOut = function(t, b, c, d) {
    t /= d/2;
    if (t < 1) return c/2*t*t + b;
    t--;
    return -c/2 * (t*(t-2) - 1) + b;
  };
  
  this.move = function(toStep) {
    var self = this;
    
    if (toStep === this.step || this.moving) return;
    
    this.moving = true;

    var startGlass = (this.step - 1) * 140;
    var startContent = (1 - this.step) * 600;
    var startHeight = this.steps_wrap.offsetHeight;
    
    var endGlass = (toStep - 1) * 140;
    var endContent = (1 - toStep) * 600;

    var targetNode = document.getElementById('dev_step' + toStep + '_c');
    targetNode.style.position = 'absolute';
    targetNode.style.left = '-5000px';
    var endHeight = targetNode.querySelector('.borders').offsetHeight + 12;
    targetNode.style.position = 'static';
    targetNode.style.left = 'auto';
    
    for (var i = 1; i <= 3; i++) {
      var stepEl = document.getElementById('dev_step' + i + '_c');
      stepEl.style.height = 'auto';
      stepEl.style.position = 'static';
      stepEl.style.left = 'auto';
    }
    
    var duration = 400;
    var startTime = Date.now();

    var startGlassWidth = (this.step === 3) ? 236 : 141;
    var endGlassWidth = (toStep === 3) ? 236 : 141;
    
    function animate() {
      var elapsed = Date.now() - startTime;
      var progress = Math.min(elapsed / duration, 1);
      
      var easedProgress = self.easeInOut(progress, 0, 1, 1);

      var currentGlass = startGlass + (endGlass - startGlass) * easedProgress;
      var currentContent = startContent + (endContent - startContent) * easedProgress;
      var currentHeight = startHeight + (endHeight - startHeight) * easedProgress;
      var currentGlassWidth = startGlassWidth + (endGlassWidth - startGlassWidth) * easedProgress;

      self.steps_glass.style.marginLeft = currentGlass + 'px';
      self.steps_glass_c.style.marginLeft = (-currentGlass - 2) + 'px';
      self.steps_content.style.marginLeft = currentContent + 'px';
      self.steps_wrap.style.height = currentHeight + 'px';
      self.steps_glass.style.width = currentGlassWidth + 'px';
      
      if (progress < 1) {
        requestAnimationFrame(animate);
      } else {
        self.step = toStep;
        self.moving = false;
        if (window.history && window.history.replaceState) {
          window.history.replaceState(null, null, '#devstep' + toStep);
        } else {
          location.hash = 'devstep' + toStep;
        }
      }
    }
    
    requestAnimationFrame(animate);
  };
  
  this.steps_wrap.style.height = 'auto';
  var initialHeight = document.getElementById('dev_step' + this.step + '_c').querySelector('.borders').offsetHeight + 12;
  this.steps_wrap.style.height = initialHeight + 'px';
  
  this.steps_glass.style.marginLeft = ((this.step - 1) * 140) + 'px';
  this.steps_glass_c.style.marginLeft = (-(this.step - 1) * 140 - 2) + 'px';
  this.steps_content.style.marginLeft = ((1 - this.step) * 600) + 'px';
  this.steps_glass.style.width = (this.step === 3 ? 236 : 141) + 'px';
  this.steps_glass.style.display = 'block';
  this.steps_content.style.display = 'block';
  
  for (var i = 1; i <= 3; i++) {
    var stepElement = document.getElementById('dev_step' + i + '_c');
    stepElement.style.height = 'auto';
    stepElement.style.position = 'static';
    stepElement.style.left = 'auto';
  }
}

function dev_step(toStep) {
  if (nav) {
    nav.move(toStep);
  }
  return false;
}

function onDomReady(callback) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', callback);
  } else {
    callback();
  }
}

window.prevSection = false;

function slideSection(obj, directly) {
  if (!directly) obj = obj.parentNode.children[1];
  
  if (window.prevSection && window.prevSection !== obj) {
    window.prevSection.style.display = 'none';
  }
  
  window.prevSection = obj;
  obj.style.display = obj.style.display === 'none' ? 'block' : 'none';
}

onDomReady(function() {
  var step = 1;
  var hash = location.hash;
  var match = hash.match(/devstep(\d)/);
  
  if (match) {
    var hashStep = parseInt(match[1]);
    if (hashStep >= 1 && hashStep <= 3) {
      nav = new init_dev_steps(hashStep);
      dev_step(hashStep);
    }
  } else {
      nav = new init_dev_steps(1);
  }
});