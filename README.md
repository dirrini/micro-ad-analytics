# ImpressionTrack: Real-Time Ad Tech Analytics Engine

ImpressionTrack is a decoupled, containerized full-stack analytics engine designed to mimic high-throughput ad-network infrastructures (like DSPs/SSPs). It captures incoming impression tracking pixels via a stateless backend, pipes events instantly through a dedicated WebSocket microservice, and paints reactive, live metrics onto a modern dashboard without forcing user-initiated interface refreshes.

## 🚀 Key Architectural Features
* **Decoupled Stateless Ingestion:** Built to optimize high concurrent throughput, isolating raw data tracking logic from the customer analytics dashboard layer.
* **Sub-Millisecond Live Updates:** Uses a custom Node.js WebSocket broadcast server powered by a low-timeout webhook mechanism triggered directly inside the ingestion lifecycle.
* **Smart User-Agent Parsing:** Implements refined signature identification ordering to accurately capture modern Chromium-based browsers (Edge, Opera) alongside standard agents (Chrome, Safari, Firefox).
* **Multi-Stage Container Layering:** Leverages isolated environments wrapped in a clean Docker Compose layout, optimizing build caching and serving production assets cleanly via an Nginx proxy.

---

## 🛠️ Tech Stack & Ecosystem
* **Backend Ingestion:** PHP (Stateless Pixel Endpoint), MySQL 8.0 (Storage Layer)
* **Real-Time Layer:** Node.js (WebSocket Server, Event Broadcaster)
* **Frontend Layer:** Vue.js 3 (Composition API), Chart.js, Vite
* **Infrastructure:** Docker, Docker Compose, Nginx

---

## 📁 Repository Structure

impression-track/
├── backend/            # Stateless PHP ingestion tracking & API endpoint
├── ws-server/          # Node.js WebSocket microservice
├── frontend/           # Vue 3 application source code (Vite-powered)
├── database/           # Auto-provisioned MySQL migration schemas
└── docker-compose.yml  # Multi-container multi-network mesh layer

---

## ⚡ Quick Start & Deployment

### Prerequisites
Make sure you have Docker Desktop installed on your machine.

### 1. Run the Container Environment
Clone the repository and spin up the complete isolated network environment using a single command:

docker compose up -d --build

This automatically boots, links, and runs:
* **The DB Layer** on port 3306 (with migration schemas pre-seeded)
* **The PHP Backend** proxying requests via Nginx on port 8080
* **The WebSocket Server** open on port 8085
* **The Production Frontend** listening on port 3000

---

## 🛠️ Local Development Loop

To take advantage of Hot Module Replacement (HMR) inside the Vue client layer while actively coding without waiting on constant Docker builds:

1. Keep your backend infrastructure running smoothly inside Docker.
2. Open a local shell path on your host machine to run Vite natively:
   cd frontend
   npm install
   npm run dev
3. Open the provided terminal local link (Typically http://localhost:5173) to write and test code variations instantly.
4. When finished making variations, lock in production changes by building assets before committing:
   npm run build
   docker compose up -d --build frontend

---

## 🧪 Testing Ingestion Real-Time Animations

You can instantly spoof raw impressions from multiple isolated browser signatures using native terminal utilities to verify that your live charts update immediately. Run these commands directly inside your PowerShell terminal:

### Simulate Microsoft Edge
curl.exe -A "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0" "http://localhost:8080/track.php?cid=q2_launch"

### Simulate Apple Safari
curl.exe -A "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15" "http://localhost:8080/track.php?cid=state_of_play"

### Simulate Opera
curl.exe -A "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0" "http://localhost:8080/track.php?cid=flash_sale"