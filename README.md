# GitHub synchronizer

This script lets you synchronize labels and milestones between different GitHub repositories.

## Installation

Clone the repository and install composer dependencies:

```
git clone https://github.com/piwik/github-sync
composer install
```

## Usage

Simply run the `github-sync.php` script to see how to use the command:

```
./github-sync.php help sync
```

Here is an example of the full command line:

```
./github-sync.php sync piwik/piwik piwik/plugin-SiteMigration --token=12345abcd
```

The script will ask you before doing any modification so don't be afraid to run it to see if it works.

You can provide a GitHub *Personal Access Token* using the `--token` option. This is necessary if you want to create/delete/update labels. If you just want to try out the script without doing any modification, you don't need to provide a token.

## License

This tool is released under the LGPL v3.0.
