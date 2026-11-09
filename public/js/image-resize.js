// Perkecil gambar sebelum diunggah supaya tidak melewati batas upload PHP.
(function () {
  "use strict";

  var MAX_SIDE = 1200;
  var MAX_BYTES = 400 * 1024;
  var QUALITY = 0.9;

  function loadImage(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () {
        URL.revokeObjectURL(url);
        resolve(img);
      };
      img.onerror = function () {
        URL.revokeObjectURL(url);
        reject(new Error("gambar tidak terbaca"));
      };
      img.src = url;
    });
  }

  function shrink(file) {
    return loadImage(file).then(function (img) {
      var scale = Math.min(1, MAX_SIDE / Math.max(img.naturalWidth, img.naturalHeight));
      if (scale === 1 && file.size <= MAX_BYTES) return file;

      var canvas = document.createElement("canvas");
      canvas.width = Math.round(img.naturalWidth * scale);
      canvas.height = Math.round(img.naturalHeight * scale);
      canvas.getContext("2d").drawImage(img, 0, 0, canvas.width, canvas.height);

      // WebP tetap menjaga transparansi logo dan ukurannya kecil.
      return new Promise(function (resolve) {
        canvas.toBlob(function (blob) {
          if (!blob || blob.size >= file.size) return resolve(file);
          var name = file.name.replace(/\.[^.]+$/, "") + ".webp";
          resolve(new File([blob], name, { type: "image/webp" }));
        }, "image/webp", QUALITY);
      });
    });
  }

  function setBusy(form, busy) {
    form.querySelectorAll('button[type="submit"]').forEach(function (button) {
      button.disabled = busy;
    });
  }

  document.querySelectorAll('input[type="file"][accept^="image"]').forEach(function (input) {
    input.addEventListener("change", function () {
      var file = input.files && input.files[0];
      if (!file || typeof DataTransfer === "undefined") return;

      var form = input.form;
      if (form) setBusy(form, true);
      shrink(file)
        .then(function (result) {
          if (result === file) return;
          var transfer = new DataTransfer();
          transfer.items.add(result);
          input.files = transfer.files;
        })
        .catch(function () {
          // Biarkan file asli; validasi server yang akan menolak bila tidak valid.
        })
        .finally(function () {
          if (form) setBusy(form, false);
        });
    });
  });
})();
