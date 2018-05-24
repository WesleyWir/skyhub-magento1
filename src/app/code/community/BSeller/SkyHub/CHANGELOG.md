# Changelog
All notable changes to this project will be documented in this file.

## [Unreleased]

## [1.2.7] - 2018-05-24
### Added
- Solved problem with order statuses queue which wasn't decreasing magento queue;
- Solved problem with order consuming which wasn't decreasing skyhub queue;
- Solved problem with orders created with the option to create with default magento increment_id which wasn't beeing deleted from skyhub;
- Solved problem with canceled orders at skyhub which wasn't beeing deleted but magento wasn't cleaning skyhub queue;
- Solved problem with configurable products sons which wasn't being updated at skyhub by integration;

### Changed
- Minor fixes.

## [1.2.6] - 2018-05-17
### Added
- Added a script file to execute all integrations (line creation and execution).
- Added logic to create a customer email if the order doesn't contains it. A email creation pattern can be configurated at module config panel.
- Products integration now is called after a order placed too (only for the order items). This behavior can be configured at module config panel. 

### Changed
- Minor fixes.
- Changed the behavior of "deleted" product. Now, the product will be disabled and the stock set to 0 at skyhub.

## [1.2.5] - 2018-04-25
### Added
- Now, it's available an option to use the magento default incrementId at order creation process.
### Changed
- Adapting the order process creation to do not conflict with "Bizz Commerce" skyhub module;

## [1.2.4] - 2018-04-19
### Changed
- Minor fixes.

## [1.2.3] - 2014-04-06
### Changed
- Minor fixes.

## [1.2.2] - 2018-04-06
### Changed
- Minor fixes.

## [1.2.1] - 2018-04-05
### Changed
- Minor fixes.  

## [1.2.0] - 2018-03-29
### Changed
- Product integration fixes. Now only the changed products are being queued for integration.
- Changed validation to display the attributes mapping notification in admin.
- Changes to product attributes requirements. Now, only some attributes are really required for product integration.
- Created a product visibility filter in module configuration. Now you can select what product visibility can be integrated.
- Fix to first and last names when importing an order from SkyHub. Now, if there’s only the first name in the order the last name will be the same for first name as well.
- Fix to customer telephone in order import. If the customer does not provide a phone number a default number will be created.
- Minor fixes.

## 1.0.0 - 2018-03-14
### Added
- Products integration
- Product attributes integration
- Product categories integration
- Orders integration
- Base module
