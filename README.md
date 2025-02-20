# Laravel Filament SQLite Browser

A Filament-powered SQLite database browser for Laravel applications. This package provides a clean, intuitive interface for managing SQLite databases directly through your Filament admin panel.

## Features

- Browse all SQLite databases in your application
- View and navigate database tables
- Examine table schemas and structures
- Edit table records with dynamically generated forms
- Clean integration with Filament's UI components
- Supports multiple SQLite database connections

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- Filament 3.x

## Installation

You can install the package via composer:

```bash
composer require crumbls/sqlite-browser
```

## Usage

Once installed, a new "SQLite Browser" section will appear in your Filament admin panel. From here you can:

1. View all SQLite databases
2. Browse tables within each database
3. View and edit individual records
4. Examine table schemas

## Configuration

The package configuration file allows you to:

- Configure which databases are visible
- Set access permissions
- Customize the UI elements

## Security

Before using this package in production, ensure that:

- Only trusted users have access to the Filament admin panel
- Proper database backups are in place
- Access controls are configured appropriately

## Beta Status

⚠️ This package is currently in beta. While functional, you may encounter bugs or incomplete features. Use in production environments at your own risk.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For support, please reach out via:
- Email: sqlite@crumbls.com
- Issues: GitHub issue tracker

## Credits

- [Chase Miller](mailto:chase@crumbls.com) - Package Author
- [Laravel Filament](https://filamentphp.com) - Admin Panel Framework

## License

This package is licensed under the MIT License. See the LICENSE file for details.
