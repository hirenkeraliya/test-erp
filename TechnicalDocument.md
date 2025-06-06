
### Technical Document

### Tech stack:

- Technology: PHP 8.2
- Framework: Laravel 10
- Database: MySQL 8.0
- Frontend: Vue.js 3
- CSS Framework: Tailwind CSS
- State Management: Pinia
- Package Manager: Composer
- Package Manager: NPM 10.9.2
- Package Manager: Node 18.18

### Tools:

- **Pest**:
    - Installing Pest PHP Testing Framework is a simple process that can be completed in just a few steps.
    We are using the pest php for test cases. We are writing all the test cases for every module.
    Doc Link: https://pestphp.com/docs/installation

- **PHPStan**:
    - PHPStan scans your whole codebase and looks for both obvious & tricky bugs. Even in those rarely executed if statements that  certainly aren't covered by tests.
    Doc Link: https://phpstan.org/user-guide/getting-started

- **Rector**:
    - RectorPHP is a powerful PHP refactoring and upgrading tool. It automates code transformations, making it easier to modernize legacy PHP codebases. Automates repetitive and time-consuming refactoring tasks. Helps in restructuring code to improve readability, maintainability, and consistency. Developer can define custom transformation rules specific to their projects.
    Doc Link: https://github.com/rectorphp/rector

- **ESLint**:
    - ESLint is a widely-used open-source tool for linting and fixing JavaScript code. It helps developers identify and fix problems in their JavaScript code to maintain consistent coding standards, improve code quality, and catch potential bugs early.
    Doc Link: https://eslint.org/docs/latest/


### Folder Structure:

- Domain Driven Design (DDD) is a software development approach that emphasizes understanding and modeling the business domain of an application. It aims to create software that reflects the real-world problem domain, making it easier to understand, maintain, and extend over time.
- Separation of Concerns — DDD in Laravel promotes separation of concerns, which improves maintainability and testability. By dividing the application into domains, we can focus on the business logic of each domain without worrying about the technical details of the infrastructure.

- Scalability — DDD promotes a modular and layered architecture, which makes it easier to scale the application as it grows. Each domain can be developed and scaled independently, without affecting the rest of the application.

- Reusability — By modeling the business domain using DDD principles, we can create reusable components that can be shared across multiple applications. This can save time and effort when building new applications or extending existing ones.

- Flexibility — DDD provides a flexible and adaptable architecture that can accommodate changing business requirements. By focusing on the business domain, we can quickly and easily modify the application to reflect new business needs.

### Example:

app
├── Console
├── Domains
│   ├── Products
│   │   ├── DataObjects
│   │   ├── DataPrepare
│   │   ├── Enums
│   │   ├── Events
│   │   ├── Exports
│   │   ├── Imports
│   │   ├── Jobs
│   │   ├── Listeners
│   │   ├── Resources
│   │   ├── Rules
│   │   ├── Services
│   │   ├── ProductQueries.php

- **App**: This is the main application directory in Laravel. It contains the core code of your application, such as business logic, data handling, and application-specific functionalities.

- **Domains**: This directory contains the business logic of the application. Each domain is a separate module that handles a specific part of the application's functionality.

- **Product**: This is a subdomain within the Domains directory, focusing on Product-related functionalities.

- **DataObjects**: This directory contains the data objects that are used to interact with the database. Each data object is a separate class that represents a table in the database.

- **DataPrepare**: This directory contains the data prepare that are used to prepare the data for the database. Each data prepare is a separate class that represents a data prepare for the database.

- **Enums**: This directory contains the enums that are used to define the enums for the database. Each enum is a separate class that represents a enum for the database.

- **Events**: This directory contains the events that are used to define the events for the database. Each event is a separate class that represents a event for the database.

- **Exports**: This directory contains the exports that are used to define the exports for the database. Each export is a separate class that represents a export for the database.

- **Imports**: This directory contains the imports that are used to define the imports for the database. Each import is a separate class that represents a import for the database.

- **Jobs**: This directory contains the jobs that are used to define the jobs for the database. Each job is a separate class that represents a job for the database.

- **Listeners**: This directory contains the listeners that are used to define the listeners for the database. Each listener is a separate class that represents a listener for the database.

- **Resources**: This directory contains the resources that are used to define the resources for the database. Each resource is a separate class that represents a resource for the database.

- **Rules**: This directory contains the rules that are used to define the rules for the database. Each rule is a separate class that represents a rule for the database.

- **Services**: This directory contains the services that are used to define the services for the database. Each service is a separate class that represents a service for the database.

- **ProductQueries.php**: This file contains the queries that are used to define the queries for the database. Each query is a separate class that represents a query for the database.


### For developer guide: Here this file name is DeveloperGuidelines.md for developer coding standard to all information how to do code what is the best practice and how to do code.
[Click here to go to the file](DeveloperGuidelines.md)

### For project setup please follow the this [README](README.md)
