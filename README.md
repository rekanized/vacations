# AbsenceBoard

![AbsenceBoard logo](public/brand/absenceboard-mark.svg)

A premium, handcrafted Laravel application for team leave planning and availability visualization.

[☕ Buy me a coffee](https://buymeacoffee.com/rekanized)

---

## 🚀 Quick Start (Docker Hub)

Run the full stack without cloning the repository. Create a `docker-compose.yml`:

```yaml
services:
  app:
    image: rekanized/absenceboard:v1.0.0
    restart: unless-stopped
    volumes:
      - /app/vendor
      - ./database:/app/database
      - ./storage:/app/storage
  nginx:
    image: rekanized/absenceboard-nginx:v1.0.0
    restart: unless-stopped
    ports:
      - "8000:80"
    depends_on:
      - app
```

Then run:
```bash
docker compose up -d
```
Visit `http://localhost:8000` to start the first-run setup.

---

## ✨ Features

- **Visual Planner**: Multi-month view with drag-selection and holiday markers.
- **Flexible Requests**: Custom absence types, labels, and color-coded statuses.
- **Approval Workflow**: Manager-based approval flow with rejection reasons.
- **Smart Holidays**: Country-aware public holiday support (e.g., SE, UK).
- **Admin Suite**: Dedicated workspace for user management and app settings.
- **Enterprise Ready**: Full Azure/Microsoft sign-in support.

## 🛠 Tech Stack

- **PHP 8.3 / Laravel 13**
- **Livewire 4**
- **Vanilla CSS** (No Tailwind, No Bootstrap)
- **SQLite** (Default)

## 📖 Documentation

For detailed guides, please refer to:
- [Authentication Setup](GEMINI.md#authentication)
- [Local Development Guide](GEMINI.md#local-setup)
- [Admin Workspace Details](GEMINI.md#admin-workspace)

## License

This project is open-source under the [MIT license](LICENSE).
