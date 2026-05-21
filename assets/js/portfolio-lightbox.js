
document.addEventListener("DOMContentLoaded", function () {
  const modal = document.createElement("div");
  modal.className = "ya-lightbox";
  modal.setAttribute("aria-hidden", "true");
  modal.innerHTML = `
    <div class="ya-lightbox-backdrop" data-lightbox-close></div>
    <div class="ya-lightbox-panel" role="dialog" aria-modal="true" aria-label="Image preview">
      <button type="button" class="ya-lightbox-close" data-lightbox-close aria-label="Close image preview">×</button>
      <button type="button" class="ya-lightbox-nav ya-lightbox-prev" aria-label="Previous image">‹</button>
      <img class="ya-lightbox-image" src="" alt="">
      <button type="button" class="ya-lightbox-nav ya-lightbox-next" aria-label="Next image">›</button>
      <div class="ya-lightbox-caption"></div>
    </div>
  `;
  document.body.appendChild(modal);

  const image = modal.querySelector(".ya-lightbox-image");
  const caption = modal.querySelector(".ya-lightbox-caption");
  const prev = modal.querySelector(".ya-lightbox-prev");
  const next = modal.querySelector(".ya-lightbox-next");

  let items = [];
  let currentIndex = 0;

  function collectGroup(trigger) {
    const group = trigger.getAttribute("data-lightbox-group") || "default";
    return Array.from(document.querySelectorAll('[data-lightbox-src][data-lightbox-group="' + CSS.escape(group) + '"]'));
  }

  function show(index) {
    if (!items.length) return;

    currentIndex = (index + items.length) % items.length;
    const item = items[currentIndex];

    image.src = item.getAttribute("data-lightbox-src");
    image.alt = item.getAttribute("data-lightbox-title") || "Portfolio image preview";
    caption.textContent = item.getAttribute("data-lightbox-title") || "";

    const multi = items.length > 1;
    prev.style.display = multi ? "" : "none";
    next.style.display = multi ? "" : "none";
  }

  function open(trigger) {
    items = collectGroup(trigger);
    currentIndex = items.indexOf(trigger);
    if (currentIndex < 0) currentIndex = 0;

    show(currentIndex);

    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("lightbox-open");
  }

  function close() {
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("lightbox-open");
    image.src = "";
  }

  document.addEventListener("click", function (event) {
    const trigger = event.target.closest("[data-lightbox-src]");
    if (trigger) {
      event.preventDefault();
      open(trigger);
      return;
    }

    if (event.target.closest("[data-lightbox-close]")) {
      close();
    }
  });

  prev.addEventListener("click", function () {
    show(currentIndex - 1);
  });

  next.addEventListener("click", function () {
    show(currentIndex + 1);
  });

  document.addEventListener("keydown", function (event) {
    if (!modal.classList.contains("is-open")) return;

    if (event.key === "Escape") close();
    if (event.key === "ArrowLeft") show(currentIndex - 1);
    if (event.key === "ArrowRight") show(currentIndex + 1);
  });
});
