### Developer Guidelines

#### Documentation
- Document new features and update the documentation for old features. Store all documentation in the designated documentation repository or platform (e.g., Confluence, GitHub Wiki).
- Ensure that API documentation is kept current and accurate using tools like Swagger or API Blueprint.

#### Code Quality and Testing
- Be mindful of the side effects your code might cause.
- Automated tests are mandatory, regardless of task urgency.
- Perform manual testing before creating a Pull Request (PR).
- Ensure the UI/UX is user-friendly. If you encounter issues that you cannot resolve, report them to the team leader.
- Maintain high code quality at all times; no compromises.
- Adhere to PSR (PHP Standards Recommendations) for code style and formatting.
- Conduct usability testing and gather feedback to continually improve the user interface.
- Indent using 4 spaces

#### High Risk: These Rules Will Cause System Failures
- Need to take care for the Product Merge. for that need to take care for the product_id and it's inventory and inventory related relations.
- Need to take care for the Member Merge

#### Code Structure
- Avoid unnecessary `if-else` conditions.
- Do not write all code in a single method.
- Prefer Laravel collections over array functions.
- Break your code into multiple methods/classes. Seek help from the Team Leader if needed.
- Use Eloquent ORM for all database interactions. Avoid direct DB queries (`DB::insert`, etc.).
- Follow Domain Driven Design principles; place module-specific code in the appropriate folder.
- Explicitly specify columns when fetching data from the database.
- Use database transactions for multiple write operations.
- Avoid abort inside the DB transactions.
- Implement query scoping for checks (e.g., Company, Store, Warehouse, Status).
- Maintain backward compatibility and handle old data when modifying existing functionality.
- Write efficient and performant code; avoid unnecessary computations or database calls.
- If you update anything in [StaticDataSeeder.php](https://github.com/JakelFadzly/pos-backoffice/blob/main/database/seeders/StaticDataSeeder.php) file. Prepare a script to update old data.
- Whenever you create a new model Add to ModelMapping.php


#### API and Data Management
- Update the Postman collection when working on APIs.
- Specify comprehensive validation rules for all form inputs.
- Always review your code before requesting a reviewer and set appropriate labels.
- Ensure proper user permissions for any new routes/URLs & PermissionModuleService.php.
- Regularly update dependencies to include the latest security patches.
- use `PUT` method whenever record updates or critical transaction happen to block duplicate request(BlockDuplicatePostRequests.php)
- use `PUT` in main object if form have image upload functionality. [Ref.](https://github.com/JakelFadzly/pos-backoffice/pull/6083/files)

#### Database Practices
- Always create new migrations for database schema updates.
- Prefix migration, jobs, or commands related to old data migration with `Temporary`.
- Skip the `down()` method in migrations as rollbacks are rarely used.
- Ensure table alignment:
  - Right-align columns with amounts or digits.
  - Left-align text columns.
  - Center-align action columns.

#### Pull Requests and Collaboration
- Remove all `dd`, `dump`, and `console.log` statements before submitting a PR.
- Write clear titles and descriptions for PRs and link the relevant Notion task.
- Inform your team leader of any additional software or PHP extensions required by new libraries/packages.
- add/update comments in package.json/composer.json for the purpose of the package.
- Prefer creating 10 small PRs over one large PR.
- Reuse code wherever possible instead of duplicating it.
- Follow the [Clean Code PHP guidelines](https://github.com/jupeter/clean-code-php).
- Complete existing PRs before starting new tasks.
- Enable GitHub notifications for PR changes or review requests to avoid personal reminders.
- Use meaningful commit messages that accurately describe the changes made.
- Engage actively in code reviews; provide constructive feedback and be open to receiving it.
- Collaborate with team members to resolve complex issues or blockers.
- Pair programming is encouraged for difficult tasks or critical features.

#### Security
- Ensure your code adheres to security best practices (e.g., SQL injection prevention, XSS protection, CSRF protection).
- Use environment variables for sensitive information and avoid hardcoding credentials.
- Regularly update dependencies to include the latest security patches.
- Implement comprehensive error handling and logging mechanisms.
- Use Laravel’s logging system to log errors and important events.
- Ensure that critical issues trigger alerts to the appropriate team members.

#### Performance
- Write efficient and performant code; avoid unnecessary computations or database calls.
- Optimize database queries to minimize load times and improve responsiveness.
- Monitor application performance and address any bottlenecks proactively.

#### Deployment and DevOps
- Ensure code is compatible with the CI/CD pipeline.
- Verify that the deployment scripts are up-to-date and tested.
- Maintain documentation for deployment processes and rollback procedures.

#### Learning and Development
- Stay updated with the latest Laravel and PHP features and best practices.
- Participate in team training sessions or workshops.
- Share knowledge and learnings from conferences, articles, or courses with the team.

#### Team and Tools
- Use Ubuntu OS as standardized. If using a different OS, you are responsible for resolving any issues.
- Adhere to the naming conventions below:
  - **Files/Directories**: `pos_erp`
  - **CSS File**: `pos_erp.css`
  - **JS File**: `pos_erp.js`
  - **Image File**: `pos-erp.png`
  - **Laravel View File**: `pos_erp.blade.php`
  - **HTML Element Class Name**: `pos-erp`
  - **Input Name**: `pos_erp`
  - **Hidden Input Name**: `_pos_erp`
  - **Element ID Name**: `pos-erp`
  - **Class Name**: `PosErp`
  - **Function Name**: `posErp`
  - **Variable Name**: `posErp`
  - **Enum Case**: `POS_ERP`
  - **Route Name**: `pos_erp`
  - **Route URL**: `pos-erp`
  - **Database & Column Name**: `pos_erp`

#### Miscellaneous
- Protect the main branch.
- Our activity log is based on the Eloquent model. We configure the activity log on the base model file. For authenticatable models, configure the activity log manually.
- Work is organized in weekly sprints. Performance will be evaluated based on sprint completion.
- Always add a new empty line at the end of the file.
- Do proper indentation of your written code.
- Add proper notes in the UI when necessary to improve user experience.
- When creating a feature, add a note in the PR and inform your team leader if any library/package requires extra software or PHP extension installation.
- Always use Eloquent to add or update DB queries. (Not direct DB::insert, etc.)
- Jobs should be dispatched with specific queue connection refer the horizon.php config.

---

These additions ensure that your guidelines are not only comprehensive but also detailed and clear, providing a robust framework for maintaining code quality, security, performance, and collaboration within the team.
