# How This Works

After installing this package and running the command `php artisan apidoc:generate` in your application, here's what happens:

- The package fetches all your application's routes.
- It looks through your [configuration file](config.md) to filter the routes to the ones you actually want to document. For each route, it retrieves the settings you want to apply to it, if any.
- It processes each route. "Process" here involves using a number of strategies to extract the route's information: group, title, description, body parameters, query parameters, and a sample response, if possible.
- After processing the routes, it generates a markdown file describing the routes from the parsed data and passes them to [Documentarian](https://github.com/mpociot/documentarian), which wraps them in a theme and converts them into HTML and CSS.
- It generates a Postman API collection for your routes. ([This can be disabled.](config.html#postman))
