<?php
class Plugin_twitter extends Plugin {

  var $meta = array(
    'name'       => 'Twitter',
    'version'    => '0.9',
    'author'     => 'Mubashar Iqbal',
    'author_url' => 'http://mubs.me'
  );

  public function tweets()
  {
    $name         = $this->fetchParam('name', null); // defaults to no
    $count        = $this->fetchParam('count', 10, 'is_numeric'); // defaults to no
    $show_intents = $this->fetchParam('show_intents', true, false, true); // defaults to yes

    if ($show_intents) {
      $show_intents = 1;
    } else {
      $show_intents = 0;
    }

    if ($name) {
      $js = '<div id="'.$name.'_tweets" class="tweets"></div>
        <script>
          $(document).ready(function() {
            $.getJSON("http://api.twitter.com/1/statuses/user_timeline.json?include_rts=true&screen_name='.$name.'&count='.$count.'&callback=?",
              
            function(data) {
              var re = /((http|https|ftp):\/\/[\w?=&.\/-;#~%-]+(?![\w\s?&.\/;#~%"=-]*>))/g;
              var count = 0;
              var show_intents = '.$show_intents.';
              $.each(data, function(i,item) {
                count ++;
                if (count <= '.$count.') {
                    var tweet = item.text.replace(re, \'<a target="_blank" href="$1">$1</a> \');
                    tweet = tweet.replace(/(^|\s)@(\w+)/g, \'$1<a href="http://www.twitter.com/$2" target="_blank">@$2</a>\');
                    tweet = tweet.replace(/(^|\s)#(\w+)/g, \'$1<a href="http://search.twitter.com/search?q=%23$2" target="_blank">#$2</a>\');


                    var intents = "";
                    if (show_intents) {
                      intents = intents + \'<ul class="intents">\';
                      intents = intents + \'<li><a href="https://twitter.com/intent/tweet?in_reply_to=##ID##" class="reply">Reply</a></li>\';
                      intents = intents + \'<li><a href="https://twitter.com/intent/retweet?tweet_id=##ID##" class="retweet">Retweet</a></li>\';
                      intents = intents + \'<li><a href="https://twitter.com/intent/favorite?tweet_id=##ID##" class="favorite">Favorite</a></li>\';
                      intents = intents + \'</ul>\';
                      intents = intents.replace(/##ID##/g, item.id_str);
                    } 

                    $("#'.$name.'_tweets").append("<p>"+ tweet + "</p>"+intents+"");
                }
              });

            });
          });
        </script>
        ';
      return $js;
    }

    return '';
  }

  public function profilewidget() {

    if (isset($this->attributes['name'])) {
      $return = "
  <script charset='utf-8' src='http://widgets.twimg.com/j/2/widget.js'></script>
  <script>
  new TWTR.Widget({
    version: 2,
    type: 'profile',
    rpp: 4,
    interval: 30000,
    width: 'auto',
    height: 300,
    theme: {
      shell: {
        background: '#333333',
        color: '#ffffff'
      },
      tweets: {
        background: '#000000',
        color: '#ffffff',
        links: '#4aed05'
      }
    },
    features: {
      scrollbar: false,
      loop: false,
      live: false,
      behavior: 'all'
    }
  }).render().setUser('".$this->attributes['name']."').start();
  </script>
    ";
  } else {
    $return = '';
  }

  return $return;
  }
}