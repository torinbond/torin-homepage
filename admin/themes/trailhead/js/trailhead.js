// Customize select boxes
$(function(){
	if (!$.browser.opera) {
		$('.input-select-wrap select').not('.cell-select select').each(function(){
			var title = $(this).attr('title');
			title = $('option:selected',this).text();
			$(this)
				.css({'z-index':10,'opacity':0,'appearance':'none', '-khtml-appearance':'none', '-webkit-appearance': 'none'})
				.after('<span class="select">' + title + '</span>')
				.change(function(){
					val = $('option:selected',this).text();
					$(this).next().text(val);
				})
		});
	}

	if ($("#date-submit").length) {
		$("#date-submit").find('input[type=submit]').remove();
		$("#date-submit").on("change", "select", function() {
			$(this).closest("form").submit();
		});
	}

	$('.log-sortable').tablesorter({
		headers: {
			1: {
				sorter: "hidden-date"
			}
		}
	});

	$('.sortable').tablesorter();
});

// Tablesorter
$.tablesorter.addParser({
	id: "hidden-date",
	is: function(s) {
		return false;
	},
	format: function(s, table, cell) {
		return parseInt($(cell).data("fulldate"), 10);
	},
	type: 'numeric'
});

// Tooltips
$('.tip').tooltip();

$(function() {
  $('.input-markitup textarea').markItUp(mySettings);
});

$('body').on('addRow', '.grid', function() {
  $(function() {
    $('.input-markitup textarea').markItUp(mySettings);
  });
});

// Datepicker
$('.datepicker').datepicker({format:'yyyy-mm-dd'})
	.on('changeDate', function(ev){
		$(this).datepicker('hide');
});

$('body').on('addRow', '.grid', function() {
  $('.datepicker').datepicker({format:'yyyy-mm-dd'})
    .on('changeDate', function(ev){
      $(this).datepicker('hide');
  });
});


$('.timepicker').timepicker({
	defaultTime: 'value'
});

$('body').on('addRow', '.grid', function() {
  $('.timepicker').timepicker({
    defaultTime: 'value'
  });
});

//  Suggest fieldtype
$('.chosen-select').chosen(
  {allow_single_deselect: true}
);

// Suggest fieldtype inside a grid
$('body').on('addRow', '.grid', function() {
  $('.chosen-select').chosen(
    {allow_single_deselect: true}
  );
});

// Auto slugger
$('#publish-title').makeSlug({
	slug: $('.auto-slug')
});

// if a default title was set, pre-populate the slug field
$(document).ready(function() {
	if ($("#publish-title").length && $("#publish-title").val().length > 0) {
		$("#publish-title").keyup();
	}
});

$(function() {
	$('.input-tags input').tagsInput({
		width:'auto',
		'defaultText':'add an item...'
	});
});


// In CP flash messages
$('#flash-msg')
	.delay(50)
	.animate({'margin-top' : '0'}, 900, 'easeOutBounce')
	.delay(3000)
	.animate({'margin-top' : '-74px'}, 900, 'easeInOutBack');

// Login error message
$('#login #form-errors')
	.delay(50)
	.animate({'margin-top' : '-245px'}, 500, 'easeOutBack');

// Hide iPhone address bar
window.addEventListener("load",function() {
	setTimeout(function(){
		window.scrollTo(0, 1);
	}, 0);
});


// The Almighty Grid!

var checkGridState = function($grid) {
  var opacity,
    max_rows = parseInt($grid.data("maxRows"), 10) || Infinity,
    rows = $grid.find("tbody tr").length;

  opacity = (rows >= max_rows) ? 0.2 : 1.0;
  $grid.next("a.grid-add-row").css("opacity", opacity);
}

var updateGrid = function($grid) {
  $grid.children("tbody").children("tr").each(function(i) {
    $(this).children("th").text(i + 1);
  });
};

var renameInputs = function($grid) {
  $grid
    .find("input, textarea, select")
    .each(function() {
      var positions = [];

      // get positioning of each parent <tr> within their set of <tr>s
      $(this).parents("tr").each(function() {
        positions.push($(this).parent().children("tr").index($(this)));
      });

      // reverse the array, so that root <tr> is first
      positions.reverse();

      // regex time
      var newName = $(this).attr("name").replace(/page\[[\w\d\-_]+\]\[[\w\d\-_]+](?:\[\d+\]\[[\w\d_\-]+\])+/ig, function(match) {
        var i = 0;

        return match.replace(/(\[\d+\]\[[\w\d\-_]+\])/ig, function(submatch) {
          var output = submatch.replace(/\[\d+\]/i, "[" + positions[i] + "]");

          i++;

          return output;
        });
      });

      $(this).attr("name", newName);
    });
};

var stick_helper_width = function(e, tr) {
  var $originals = tr.children();
  var $helper = tr.clone();
  $helper.css("width", "100%").children().each(function(index) {
    $(this).width($originals.eq(index).width())
  });
  return $helper;
};

var sortable_options = {
  helper: stick_helper_width,
  handle: 'th.drag-indicator',
  placeholder: 'drag-placeholder',
  forcePlaceholderSize: true,
  axis: 'y',

  start: function (event, ui) {
    var num_cols = $(this).find('tr')[0].cells.length;
    ui.placeholder.html('<td colspan='+num_cols+'>&nbsp;</td>');
  },

  update: function(event, ui) {
    $(event.target).find('> tr').each(function() {
      var row_number = $(this).index(),
          $grid = $(this).closest(".grid");

      $(this).children("th:first-child").html(row_number + 1);
      renameInputs($grid);
    });
  }
};

// add a new row to the grid
$("a.grid-add-row").live("click", function () {
  var $grid = $(this).parent().children(".grid:first"),
	    row_count = $grid.children("tbody").children("tr").length,
	    max_rows = $grid.data("maxRows") || Infinity,
      empty_row = $grid.data("emptyRow");

	if (row_count >= max_rows) {
		return false;
	}

	$grid.append(empty_row).find("table.grid tbody").sortable(sortable_options);

   renameInputs($grid);
	checkGridState($grid);
	updateGrid($grid);
	return false;
});



$("a.grid-delete-row").live("click", function() {
	var message, sublevel_target,
			$grid = $(this).closest(".grid"),
			min_rows = $grid.data("minRows") || 0;

  // if we haven't asked to confirm, do that now
  if ($(this).is(".confirm")) {
    $(this).removeClass('confirm').text("Do it.");
    return false;
  }

	// prevent row deletion if min_rows is set and this would go under that
	if ($grid.children("tbody").children("tr").length <= min_rows) {
		if (min_rows > 0) {
			message = "This grid requires at least " + min_rows + " row";
			message += (min_rows === 1) ? "." : "s.";
			alert(message);
		}

		$(this).addClass("confirm").html('<span class="icon">u</span>');
		checkGridState($grid);
		updateGrid($grid);
		return false;
	}

  // ok, remove this row
  $(this).closest('tr').remove();

  // rename inputs
  renameInputs($grid);
	checkGridState($grid);
	updateGrid($grid);
	return false;
});

$(".grid tbody").sortable(sortable_options);

$('.confirm').click(function() {
	$(this).removeClass('confirm');
	$(this).text("Do it.");
	$(this).unbind();
	return false;
});

$('.btn-remove-file').on('click', function() {
    var name = $(this).next('input').attr('name');
    $(this).parent().parent().append(
        $('<p />').append($('<input/>').attr('type', 'file').attr('name', name))
    );
    $(this).parent().remove();
});