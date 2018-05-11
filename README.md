phpcsv is a small PHP package to assist in dealing with CSV data.

It is not meant to be full-featured or very high-performance.  I regularly need
to manipulate one-off CSV data and so I built this to assist in the common
things I keep re-writing like formatting a date or merging 2 CSVs together.

A few examples:

```
// selectively join data from one CSV into another
//
// this would look at the 'id' column in file.csv and match it with 'fid' column
// in other_file.csv then take the 'username' column from other_file.csv and
// fill it into the 'user' column of file.csv.  then dump changes to stdout.
//
// it is similar to an SQL join, for example:
//   select file.*, other_file.username from file
//     left join other_file on file.id = other_file.fid
//
$csv = new CSV('file.csv');
$csv->join(new CSV('other_file.csv'), 'id', 'fid', ['user'], ['username']));
$csv->getRecords()->dump();
```

```
// format a date column, dump output
$csv = new CSV('file.csv');
$csv->setFormatter('date_of_birth', function($data) {
    return Formatters::date($data, 'm/d/Y');
});
$csv->getRecords()->dump();
```

```
// loop through file without loading into memory
foreach (new CSV('file.csv', ['preload' => false])->getRecords() as $record) {
    print_r($record->getAll());
}
```

```
// selectively print columns in arbitrary order
$csv = new CSV('file.csv');
$csv->getRecords()->pickyDump(['age','dob','name','sex']);
```

```
// merge 2 columns together, separate by space. print changes to stdout
$csv = new CSV('file.csv');
$csv->combineColumns(['first_name','last_name'], 'name');
$csv->getRecords()->dump();
```

```
// manually get or set data from a record
$csv = new CSV('file.csv');
foreach ($csv->getRecords() as $record) {
    $age = $record->get('age');
    if ($age >= 18) {
        $record->set('is_adult', 'yes');
    }
}
```

```
// filter records (in this case, any record where 'sex' column is not 'male'
// would be removed)
$csv = new CSV('file.csv');
$csv->getRecords()->filter(['sex' => 'male'])->dump();
```
