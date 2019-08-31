# Architecture
Read this page if you want a deeper understanding of how this works (for instance, for the purpose of contributing).

- When the `generate` command is run, it fetches all your application's routes from Laravel's (or DIngo's) Route facade.
- Next, the RouteMatcher uses the rules in your config to determine what routes to generate documentation for, as well as extract any specific configuration for them. This configuration is passed to the next stages.
- The Generator processes each route. This entails:
  - Fetching the route action (controller, method) via Reflection (along with their corresponding docblocks). These are used in the remaining stages below.
  - Determining and obtaining info on body parameters, query parameters and headers to be added to the route's documentation.
  - Obtaining a sample response.
- The generate command uses information from these parsed routes and other configuration to generate a Markdown file via Blade templating.
- This Markdown file is passed to Documentarian, which transforms it into HTML, CSS and JavaScript assets.
- If enabled, a Postman collection is generated as well.
