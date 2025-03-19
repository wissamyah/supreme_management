document.addEventListener("DOMContentLoaded", function () {
  // Initialize form submissions
  initializeForms();
  // Load initial user data
  loadUsers();

  function initializeForms() {
    // Add User Form
    const userForm = document.getElementById("userForm");
    if (userForm) {
      userForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch(appPaths.api + "/users/create.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
          })
          .then((data) => {
            if (data.success) {
              showToast("success", "User created successfully");
              userForm.reset();
              const modal = bootstrap.Modal.getInstance(
                document.getElementById("addUserModal")
              );
              modal && modal.hide();
              loadUsers();
            } else {
              showToast("error", data.message || "Error creating user");
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            showToast("error", "An error occurred while creating user");
          });
      });
    }

    // Edit User Form
    const editUserForm = document.getElementById("editUserForm");
    if (editUserForm) {
      editUserForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const userData = Object.fromEntries(formData.entries());

        // Only include password if it's not empty
        if (!userData.password) {
          delete userData.password;
        }

        fetch(appPaths.api + "/users/update.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(userData),
        })
          .then((response) => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
          })
          .then((data) => {
            if (data.success) {
              showToast("success", "User updated successfully");
              editUserForm.reset();
              const modal = bootstrap.Modal.getInstance(
                document.getElementById("editUserModal")
              );
              modal && modal.hide();
              loadUsers();
            } else {
              showToast("error", data.message || "Error updating user");
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            showToast("error", "An error occurred while updating user");
          });
      });
    }
  }

  function loadUsers() {
    fetch(appPaths.api + "/users/read.php")
      .then((response) => {
        if (!response.ok) throw new Error("Network response was not ok");
        return response.json();
      })
      .then((data) => {
        if (Array.isArray(data)) {
          updateUsersTable(data);
        } else {
          console.error("Invalid data format received:", data);
          showToast("error", data.message || "Error loading users");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showToast("error", "Error loading users");
      });
  }

  function updateUsersTable(users) {
    const tbody = document.querySelector("#usersTable tbody");
    if (!tbody) return;

    tbody.innerHTML = "";

    users.forEach((user) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
                <td>${escapeHtml(user.username)}</td>
                <td>${escapeHtml(user.email)}</td>
                <td>${escapeHtml(user.full_name)}</td>
                <td>${escapeHtml(user.role)}</td>
                <td>
                    <span class="badge ${
                      user.status === "Active" ? "bg-success" : "bg-danger"
                    }">
                        ${escapeHtml(user.status || "Active")}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary edit-user" data-id="${
                      user.id
                    }">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-warning toggle-status" data-id="${
                      user.id
                    }" data-status="${user.status || "Active"}">
                        <i class="bi bi-${
                          (user.status || "Active") === "Active"
                            ? "lock"
                            : "unlock"
                        }"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-user" data-id="${
                      user.id
                    }">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
      tbody.appendChild(tr);
    });

    // Attach event listeners to new buttons
    attachButtonListeners();
  }

  function attachButtonListeners() {
    // Edit button listeners
    document.querySelectorAll(".edit-user").forEach((button) => {
      button.addEventListener("click", function () {
        const userId = this.dataset.id;
        fetch(appPaths.api + `/users/read.php?id=${userId}`)
          .then((response) => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
          })
          .then((user) => {
            if (user.id) {
              document.getElementById("editId").value = user.id;
              document.getElementById("editUsername").value = user.username;
              document.getElementById("editEmail").value = user.email;
              document.getElementById("editFullName").value = user.full_name;
              document.getElementById("editRole").value = user.role;
              document.getElementById("editPassword").value = ""; // Clear password field

              const editModal = new bootstrap.Modal(
                document.getElementById("editUserModal")
              );
              editModal.show();
            } else {
              showToast("error", "Error loading user data");
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            showToast("error", "Error loading user data");
          });
      });
    });

    // Toggle status button listeners
    document.querySelectorAll(".toggle-status").forEach((button) => {
      button.addEventListener("click", function () {
        const userId = this.dataset.id;
        const currentStatus = this.dataset.status || "Active";
        const newStatus = currentStatus === "Active" ? "Blocked" : "Active";

        showConfirmDialog(
          "Confirm Status Change",
          `Are you sure you want to ${
            currentStatus === "Active" ? "block" : "unblock"
          } this user?`,
          () => {
            fetch(appPaths.api + "/users/update.php", {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
              },
              body: JSON.stringify({
                id: userId,
                status: newStatus,
              }),
            })
              .then((response) => {
                if (!response.ok)
                  throw new Error("Network response was not ok");
                return response.json();
              })
              .then((data) => {
                if (data.success) {
                  showToast("success", "User status updated successfully");
                  loadUsers();
                } else {
                  showToast(
                    "error",
                    data.message || "Error updating user status"
                  );
                }
              })
              .catch((error) => {
                console.error("Error:", error);
                showToast("error", "Error updating user status");
              });
          }
        );
      });
    });

    // Delete button listeners
    document.querySelectorAll(".delete-user").forEach((button) => {
      button.addEventListener("click", function () {
        const userId = this.dataset.id;

        showConfirmDialog(
          "Confirm Deletion",
          "Are you sure you want to delete this user? This action cannot be undone.",
          () => {
            fetch(appPaths.api + "/users/delete.php", {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
              },
              body: JSON.stringify({
                id: userId,
              }),
            })
              .then((response) => {
                if (!response.ok)
                  throw new Error("Network response was not ok");
                return response.json();
              })
              .then((data) => {
                if (data.success) {
                  showToast("success", "User deleted successfully");
                  loadUsers();
                } else {
                  showToast("error", data.message || "Error deleting user");
                }
              })
              .catch((error) => {
                console.error("Error:", error);
                showToast("error", "Error deleting user");
              });
          }
        );
      });
    });
  }

  // Utility function to escape HTML and prevent XSS
  function escapeHtml(unsafe) {
    if (!unsafe) return "";
    return unsafe
      .toString()
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
});
