---
paths:
  - resources/css/app.css
---

# Css

## Add vendor Blade view paths to Tailwind v4 @source
Tailwind v4 only scans the paths listed in @source. Any Composer package that ships Blade views must have its view directory added to @source in resources/css/app.css, otherwise its utility classes are never compiled and the component renders unstyled (raw HTML) even though its JS/PHP features work. Currently listed: resources/views, Laravel pagination, livewire/flux + flux-pro stubs, and rappasoft/laravel-livewire-tables/resources/views. When adding a new UI package, add its @source line and re-run `npm run build`.
