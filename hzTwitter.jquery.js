(function( $ ){


  var months = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December"
  ];

  methods = {
    
    init: function( options ) {

      options.settings = $.extend( {
      	  maxItems: 5,
      	  tweetTo: '',
      	  feeds: [],
      	  el: this
          }, options );
          
      methods.run.apply( options );
      
    },
    
    run: function() {
      var _me = this;
      
      $.post('/wp-admin/admin-ajax.php', {
      	action: 'hz_twitter_ajax',
      	feeds: this.settings.feeds
      }, function( response ) {
        methods.addItems.call( _me, response );
      }, 'xml');
    },
    
    getElapsedTime: function( d ) {
    	var now = new Date();
    	var elapsed = now.getTime() - d.getTime();
    	var timeParts = {
    		day: Math.floor(elapsed / 1000 / 60 / 60 / 24),
    		hour: function() {
    			var d = (elapsed / 1000 / 60 / 60 / 24);
    			var diff = d - Math.floor(d);
    			return d > 0 ? Math.floor(diff*24) : 0;
    		}(),
    		minute: function() {
    			var h = elapsed / 1000 / 60 / 60;
    			var diff = h - Math.floor(h);
    			return h > 0 ? Math.floor(diff*60) : 0;
    		}(), 
    		second: function() {
    			var m = elapsed / 1000 / 60;
    			var diff = m - Math.floor(m);
    			return Math.floor(diff*60);
    		}()
    	}

      var timestring;
        $.each(timeParts, function(key, value) {
          if ( value > 0) {
            timeString = [
              value,
              key + (value > 1 ? 's' : ''),
              'ago' 
            ].join(' ');
            return false;
          }
        });
      return timeString;
    	
    },
    	
    addItems: function( xml ) {
      var _me = this;

      	$( xml ).find('status:lt('+this.settings.maxItems+')').each(function(i) {
      	    
      	    var item = methods.getItem({
          	  d: new Date($(this).find('created_at').first().text()),
          		tweetURL: $(this).find('sourceURL').text(),
          		tweetText: $(this).find('text').text()
      	    }).delay(500*i).slideDown(400);
      	        
        		$( _me.settings.el ).find('li.button').before( item );
        		
      	});
	    
    	},
    	
    	getItem: function( data ) {

        return $('<li />').append(
    			$('<span />').addClass('tweet').text( data.tweetText ),
    			$('<a />').addClass('url').attr('href', data.tweetURL).text( data.tweetURL ),
    			$('<span />').addClass('date').text(
    				[
    					months[ data.d.getMonth() + 1],
    					' ',
    					data.d.getDate(),
    					', ',
    					,
    					data.d.getFullYear()
    				].join(' ')
    			),
    			$('<span />').addClass('time').text(
    				[
    					data.d.getHours() > 12 ? data.d.getHours() - 12 : data.d.getHours(),
    					':',
    					data.d.getMinutes() > 9 ? data.d.getMinutes() : '0' + data.d.getMinutes(),
    					' ',
    					data.d.getHours() > 12 ? 'p.m.' : 'a.m.'
    				].join('')
    			),
    			$('<span />').addClass('elapsed').text( methods.getElapsedTime( data.d ) ),
    			$('<span />').addClass('via').text($(this).find('source').first().text())
    			
    		).css('display','none');
    	  
    	}
      
  };
  


  $.fn.hzTwitter = function( method ) {

      if ( methods[method] ) {
        return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
      } else if ( typeof method === 'object' || ! method ) {
        return methods.init.apply( this, arguments );
      } else {
        $.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
      }    
  
  };


})( jQuery );