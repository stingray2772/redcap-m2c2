$(document).ready(function() {
    // Checking if field annotation is present on this page.
    if ($('#div_field_annotation').length === 0) {
        return false;
    }

    $('body').on('dialogopen', function(event, ui) {
        var $popup = $(event.target);
        if ($popup.prop('id') !== 'action_tag_explain_popup') {
            alert("Not the popup we are looking for...");
            // That's not the popup we are looking for...
            return false;
        }

        alert("Popup found!")

        // Aux function that checks if text matches the "@M2C2" string.
        var isDefaultLabelColumn = function() {
            return $(this).text() === '@M2C2';
        }

        // Getting @M2C2 row from action tags help table.
        var $default_action_tag = $popup.find('td').filter(isDefaultLabelColumn).parent();
        if ($default_action_tag.length !== 1) {
            alert("Action tag row not found...");
            return false;
        }

        alert("Action tag row found...")

        var tag_name = '@M2C2';
        var descr = 'M2C2 JS description goes here...';

        // Creating a new action tag row.
        var $new_action_tag = $default_action_tag.clone();
        var $cols = $new_action_tag.children('td');
        var $button = $cols.find('button');

        // Column 1: updating button behavior.
        $button.attr('onclick', $button.attr('onclick').replace('@M2C2', tag_name));

        // Columns 2: updating action tag label.
        $cols.filter(isDefaultLabelColumn).text(tag_name);

        // Column 3: updating action tag description.
        $cols.last().html('').html(descr);

        // Placing new action tag.
        $new_action_tag.insertAfter($default_action_tag);
    });
});
