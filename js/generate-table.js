function generateTable(rowsData, titles, type, _class) {
    var $table = $("<table>").addClass(_class);
    var $tbody = $("<tbody>").appendTo($table);

    
    if (type == 2) {//vertical table
        if (rowsData.length !== titles.length) {
            console.error('rows and data rows count does not match');
            return false;
        }
        titles.forEach(function (title, index) {
            var $tr = $("<tr>");
            $("<th>").html(title).appendTo($tr);
            var rows = rowsData[index];
            rows.forEach(function (html) {
                $("<td>").html(html).appendTo($tr);
            });
            $tr.appendTo($tbody);
        });
        
    } else if (type == 1) {//horizontal table 
        var valid = true;
        rowsData.forEach(function (row) {
            if (!row) {
                valid = false;
                return;
            }

            if (row.length !== titles.length) {
                valid = false;
                return;
            }
        });

        if (!valid) {
            console.error('rows and data rows count doe not match');
            return false;
        }

        var $tr = $("<tr>");
        titles.forEach(function (title, index) {
            $("<th>").html(title).appendTo($tr);
        });
        $tr.appendTo($tbody);

        rowsData.forEach(function (row, index) {
            var $tr = $("<tr>");
            row.forEach(function (html) {
                $("<td>").html(html).appendTo($tr);
            });
            $tr.appendTo($tbody);
        });
    }

    return $table;
}