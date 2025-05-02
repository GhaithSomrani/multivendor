$(document).ready(function() {
    replaceSelectWithAutocomplete('id_customer', 'searchCustomers');

    replaceSelectWithAutocomplete('id_supplier', 'searchSuppliers');
});

/**
 * Replace a select element with an autocomplete text input
 * @param {string} selectName - The name attribute of the select element
 * @param {string} ajaxAction - The AJAX action to call for search results
 */
function replaceSelectWithAutocomplete(selectName, ajaxAction) {
    var selectElement = $('select[name="' + selectName + '"]');
    if (selectElement.length === 0) return;

    var currentValue = selectElement.val();
    var currentText = selectElement.find('option:selected').text();

    var container = $('<div class="autocomplete-container form-group"></div>');

    var hiddenInput = $('<input type="hidden" name="' + selectName + '" value="' + currentValue + '">');

    var textInput = $('<input type="text" id="search_' + selectName + '" class="form-control" value="' + currentText + '" placeholder="Type at least 3 characters to search">');

    var resultsContainer = $('<div id="' + selectName + '_results" class="list-group" style="position:absolute; z-index:1000; width:100%; display:none;"></div>');

    selectElement.after(container);
    container.append(textInput, hiddenInput, resultsContainer);
    selectElement.hide();

    textInput.on('keyup', function() {
        var query = $(this).val();
        if (query.length < 3) {
            resultsContainer.hide();
            return;
        }

        $.ajax({
            url: currentIndex + '&ajax=1&action=' + ajaxAction + '&token=' + token,
            type: 'POST',
            data: {
                controller: 'AdminVendors',
                q: query
            },
            dataType: 'json',
            success: function(data) {
                resultsContainer.empty();

                if (data.length === 0) {
                    resultsContainer.append('<div class="list-group-item">No results found</div>');
                } else {
                    $.each(data, function(i, item) {
                        var displayText = '';
                        var itemId = '';

                        if (ajaxAction === 'searchCustomers') {
                            displayText = item.firstname + ' ' + item.lastname + ' (' + item.email + ')';
                            itemId = item.id_customer;
                        } else if (ajaxAction === 'searchSuppliers') {
                            displayText = item.name;
                            itemId = item.id_supplier;
                        }

                        var listItem = $('<a href="#" class="list-group-item">' + displayText + '</a>');

                        listItem.data('id', itemId);
                        listItem.on('click', function(e) {
                            e.preventDefault();
                            hiddenInput.val($(this).data('id'));
                            textInput.val($(this).text());
                            resultsContainer.hide();
                        });

                        resultsContainer.append(listItem);
                    });
                }

                resultsContainer.show();
            }
        });
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#search_' + selectName + ', #' + selectName + '_results').length) {
            resultsContainer.hide();
        }
    });
}