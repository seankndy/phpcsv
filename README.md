phpcsv is a small PHP package to assist in dealing with CSV data.

It is not meant to be full-featured or high-performance.  I regularly need to
manipulate one-off CSV data and so I built this to assist in the common
things I keep re-writing like formatting a date or merging 2 CSVs together.

A few examples:

```
// selectively join data from one CSV into another
//
// this would look at the 'id' column in file.csv and match it with 'fid' column
// in other_file.csv then take the 'username' column from other_file.csv and
// fill it into the 'user' column of file.csv.  then dump changes to stdout.
$csv = new CSV('file.csv');
$csv->join(new CSV('other_file.csv'), 'id', 'fid', ['user'], ['username']));
$csv->dump();
```

```
// format a date column, dump output
$csv = new CSV('file.csv');
$csv->setFormatter('date_of_birth', function($data) {
    Formatters::date($data, 'm/d/Y');
});
$csv->dump();
```

```
// loop through file without loading into memory
foreach (new CSV('file.csv', ['preload' => false]) as $data) {
    print_r($data);
}
```

```
// move a column, print changes to stdout
$csv = new CSV('file.csv');
$csv->moveColumn('date_of_birth', 0); //  move to beginning
$csv->dump();

```
// merge 2 columns together, separate by space. print changes to stdout
$csv = new CSV('file.csv');
$csv->combineColumns(['first_name','last_name'], 'name');
$csv->dump();
```
