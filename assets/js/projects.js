
document.addEventListener("DOMContentLoaded", function () {
  const grid = document.querySelector("[data-projects-grid]");
  if (!grid) return;

  const dataUrl = grid.getAttribute("data-projects-source") || "api/portfolio-projects.php";

  function cleanSiteUrl(siteUrl) {
    const clean = String(siteUrl || "").trim().replace(/^https?:\/\//, "").replace(/\/$/, "");
    return clean ? "https://" + clean : "";
  }

  function autoDesktop(siteUrl) {
    const url = cleanSiteUrl(siteUrl);
    return url ? "https://s0.wordpress.com/mshots/v1/" + encodeURIComponent(url) + "?w=1100" : "";
  }

  function escapeHtml(value) {
    return String(value || "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function desktopImage(project) {
    if (project.desktopMode === "manual" && project.desktopThumbnail) return project.desktopThumbnail;
    if (project.desktopThumbnail) return project.desktopThumbnail;
    return autoDesktop(project.url);
  }

  function screenshotPath(shot) {
    if (!shot) return "";
    if (typeof shot === "string") return shot;
    if (typeof shot === "object" && shot.image) return shot.image;
    if (typeof shot === "object" && shot.image_path) return shot.image_path;
    return "";
  }


  function screenshotCaption(shot) {
    if (!shot || typeof shot !== "object") return "";
    return shot.caption || shot.description || shot.title || "";
  }

  function adminGallery(project) {
    const shots = Array.isArray(project.adminScreenshots)
      ? project.adminScreenshots.map(function (shot) {
          return {
            image: screenshotPath(shot),
            caption: screenshotCaption(shot)
          };
        }).filter(function (shot) { return shot.image; })
      : [];
    if (!shots.length) return "";

    return `
      <div class="project-admin-gallery">
        <strong>Behind the scenes</strong>
        <div class="project-admin-images">
          ${shots.map(function (shot, index) {
            const caption = shot.caption || (project.name + " — behind the scenes " + (index + 1));
            return `
              <button type="button" class="project-lightbox-trigger" data-lightbox-src="${escapeHtml(shot.image)}" data-lightbox-group="${escapeHtml(project.id || project.name)}" data-lightbox-title="${escapeHtml(caption)}">
                <img src="${escapeHtml(shot.image)}" alt="${escapeHtml(caption)}" loading="lazy" />
              </button>
            `;
          }).join("")}
        </div>
      </div>
    `;
  }

  fetch(dataUrl)
    .then(function (response) {
      if (!response.ok) throw new Error("Unable to load portfolio JSON");
      return response.json();
    })
    .then(function (projects) {
      projects = (Array.isArray(projects) ? projects : []).filter(function (project) {
        return project.visible !== false;
      });

      if (!projects.length) {
        grid.innerHTML = "<p>No portfolio projects have been added yet.</p>";
        return;
      }

      grid.innerHTML = projects.map(function (project) {
        const name = escapeHtml(project.name);
        const url = escapeHtml(cleanSiteUrl(project.url));
        const description = escapeHtml(project.description);
        const location = escapeHtml(project.location || "");
        const services = Array.isArray(project.services) ? project.services : [];
        const image = escapeHtml(desktopImage(project));

        const tags = services.map(function (service) {
          return '<span class="project-tag">' + escapeHtml(service) + '</span>';
        }).join("");

        return `
          <article class="project-card">
            <div class="project-thumb" aria-label="Website preview of ${name}">
              ${image ? `<img class="project-desktop-image" src="${image}" alt="Screenshot of ${name}" loading="lazy" />` : `<div class="project-thumb-placeholder">Screenshot coming soon</div>`}
            </div>

            <div class="project-card-content">
              <h3>${name}</h3>
              ${location ? `<p class="project-location">${location}</p>` : ""}
              <p>${description}</p>
              <div class="project-tags">${tags}</div>
              ${adminGallery(project)}
              ${url ? `<a class="project-link" href="${url}" target="_blank" rel="noopener">Visit website →</a>` : ""}
            </div>
          </article>
        `;
      }).join("");
    })
    .catch(function () {
      grid.innerHTML = `<p class="portfolio-error">Portfolio projects could not be loaded. Please check the portfolio database/API.</p>`;
    });
});
