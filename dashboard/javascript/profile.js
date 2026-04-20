// Global variables for modals and currently selected course
let selectedCourse = null; // Stores the full name of the selected course

const courseModal = document.getElementById('course-modal');
const skillModal = document.getElementById('skill-modal');

// Get close buttons for modals
const closeCourseButton = courseModal ? courseModal.querySelector('.close-button') : null;
const closeSkillButton = skillModal ? skillModal.querySelector('.skill-close') : null;

// Course Modal elements
const courseSearchInput = document.getElementById('course-search');
const availableCoursesList = document.getElementById('available-courses-list');
const courseSectionSelection = document.getElementById('course-section-selection');
const courseSectionSelect = document.getElementById('course-section');
const confirmAddCourseButton = document.getElementById('confirm-add-course');

// Skill Modal elements
const skillNameInput = document.getElementById('skill-name');
const confirmAddSkillButton = document.getElementById('confirm-add-skill');

// --- Skill Modal Functions ---

/**
 * Shows the add skill modal.
 */
function showSkillModal() {
  if (skillModal) {
    skillModal.style.display = 'flex';
    if (skillNameInput) skillNameInput.focus();
  }
}

/**
 * Closes the add skill modal and clears input.
 */
function closeSkillModal() {
  if (skillModal) {
    skillModal.style.display = 'none';
    if (skillNameInput) skillNameInput.value = ''; // Clear input field
  }
}

/**
 * Handles adding a selected skill via AJAX.
 */
function addSelectedSkill() {
    const skill = skillNameInput ? skillNameInput.value.trim() : '';
    if (!skill) {
        alert('Skill name cannot be empty.');
        return;
    }

    const skillsList = document.querySelector('.skills-list');
    // Check for existing skills to prevent duplicates in UI
    const existingSkills = Array.from(skillsList.querySelectorAll('.skill-name')).map(s => s.textContent.trim().toLowerCase());

    if (existingSkills.includes(skill.toLowerCase())) {
        alert('This skill has already been added.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add_skill');
    formData.append('skill_name', skill);

    // Show loading state on button
    if (confirmAddSkillButton) {
        const originalText = confirmAddSkillButton.innerHTML;
        confirmAddSkillButton.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Adding...';
        confirmAddSkillButton.disabled = true;

        fetch('profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add the skill card to the UI dynamically
                addSkillCard(skill);
                // Close the modal
                closeSkillModal();
            } else {
                alert('Error adding skill: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error adding skill via Fetch:', error);
            alert('An error occurred while adding the skill');
        })
        .finally(() => {
            // Reset button state
            confirmAddSkillButton.innerHTML = originalText;
            confirmAddSkillButton.disabled = false;
        });
    }
}

/**
 * Dynamically adds a new skill card to the UI.
 * @param {string} skillName - The name of the skill to add.
 */
function addSkillCard(skillName) {
    const skillsList = document.querySelector('.skills-list');
    if (!skillsList) { console.error("Skills list container not found."); return; }
    const card = document.createElement('div');
    card.className = 'skill-card';

    // Escape skillName for proper use in onclick attribute to prevent syntax errors
    const escapedSkillName = skillName.replace(/'/g, "\\'");

    card.innerHTML = `
        <span class="skill-name"><i class="mdi mdi-check-bold"></i> ${htmlspecialchars(skillName)}</span>
        <button type="button" class="remove-button remove-icon" onclick="removeSkill('${escapedSkillName}')">
            <i class="mdi mdi-close"></i>
        </button>
    `;
    skillsList.appendChild(card);
}

/**
 * Handles removing a skill via AJAX.
 * @param {string} skill - The name of the skill to remove.
 */
function removeSkill(skill) {
  if (confirm('Are you sure you want to remove this skill?')) {
    const formData = new FormData();
    formData.append('action', 'remove_skill');
    formData.append('skill_name', skill);

    fetch('profile.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Remove the skill card from the UI
        const skillCards = document.querySelectorAll('.skill-card');
        skillCards.forEach(card => {
          if (card.querySelector('.skill-name') && card.querySelector('.skill-name').textContent.trim() === skill) {
            card.remove();
          }
        });
      } else {
        alert('Error removing skill: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Fetch error removing skill:', error);
      alert('An error occurred while removing the skill.');
    });
  }
}

// --- Course Modal Functions ---

/**
 * Shows the add course modal and loads available courses.
 */
function showCourseModal() {
  if (courseModal) {
    courseModal.style.display = 'flex';
    loadAvailableCourses(); // Load courses every time modal opens to ensure fresh data
    if (courseSearchInput) courseSearchInput.focus();
    // Reset selection and section display
    selectedCourse = null;
    if (availableCoursesList) {
        availableCoursesList.querySelectorAll('.course-item-selectable.selected').forEach(item => {
            item.classList.remove('selected');
        });
    }
    if (courseSearchInput) courseSearchInput.value = ''; // Clear search
    if (courseSectionSelection) courseSectionSelection.style.display = 'none'; // Hide section selection initially
    if (courseSectionSelect) courseSectionSelect.value = ''; // Clear section selection
  }
}

/**
 * Closes the add course modal and resets its state.
 */
function closeCourseModal() {
  if (courseModal) {
    courseModal.style.display = 'none';
    selectedCourse = null;
    if (courseSearchInput) courseSearchInput.value = '';
    if (availableCoursesList) availableCoursesList.innerHTML = ''; // Clear displayed courses
    if (courseSectionSelection) courseSectionSelection.style.display = 'none';
    if (courseSectionSelect) courseSectionSelect.value = '';
  }
}

/**
 * Fetches available courses from the server via AJAX.
 */
let allAvailableCourses = []; // Store the full list of courses for client-side filtering

function loadAvailableCourses() {
  fetch('profile.php?action=fetch_courses')
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
      if (data.success) {
          allAvailableCourses = data.courses; // Store the fetched courses
          displayAvailableCourses(allAvailableCourses); // Display all initially
      } else {
          console.error('Error fetching courses:', data.message);
          if (availableCoursesList) {
              availableCoursesList.innerHTML = '<div style="padding: 10px; text-align: center; color: red;">Failed to load courses.</div>';
          }
      }
    })
    .catch(error => {
        console.error('Fetch error for available courses:', error);
        if (availableCoursesList) {
            availableCoursesList.innerHTML = '<div style="padding: 10px; text-align: center; color: red;">Network error loading courses.</div>';
        }
    });
}

/**
 * Displays and filters the list of available courses in the modal.
 * @param {Array<Object>} courses - Array of course objects ({course_name, course_code}).
 */
function displayAvailableCourses(courses) {
  const container = document.getElementById('available-courses-list');
  if (!container) { console.error("availableCoursesList element not found."); return; }
  container.innerHTML = '';

  const currentSearchTerm = (courseSearchInput ? courseSearchInput.value.toLowerCase() : '').trim();

  if (courses.length === 0) {
      container.innerHTML = '<div style="padding: 10px; text-align: center; color: #888;">No courses found.</div>';
      return;
  }

  courses.forEach(course => {
      const courseName = course.course_name || '';
      const courseCode = course.course_code || '';

      // Filter logic: match full name or course code
      if (currentSearchTerm && !(courseName.toLowerCase().includes(currentSearchTerm) || courseCode.toLowerCase().includes(currentSearchTerm))) {
          return; // Skip if no match
      }

      const div = document.createElement('div');
      div.className = 'course-item-selectable';
      div.textContent = courseName; // Display full name

      // Use data attributes to store full course name and code for later retrieval on click
      div.dataset.courseName = courseName;
      div.dataset.courseCode = courseCode;

      // When clicked, select this course
      div.onclick = () => selectCourse(courseName); // Pass full course name to selector
      container.appendChild(div);
  });

    if (container.children.length === 0 && currentSearchTerm !== '') {
        container.innerHTML = '<div style="padding: 10px; text-align: center; color: #888;">No matching courses found.</div>';
    }
}


/**
 * Handles selection of a course from the available list.
 * @param {string} courseName - The full name of the course selected.
 */
function selectCourse(courseName) {
    selectedCourse = courseName; // Set the selectedCourse to the full name

    // Remove 'selected' class from all items and add to the clicked one
    document.querySelectorAll('#available-courses-list .course-item-selectable').forEach(item => {
        item.classList.remove('selected');
        if (item.dataset.courseName === courseName) { // Use dataset for reliable matching
            item.classList.add('selected');
        }
    });

    // Update the search input to reflect the selected course's full name
    if (courseSearchInput) {
        courseSearchInput.value = courseName;
    }

    // Show the section selection dropdown
    if (courseSectionSelection) {
        courseSectionSelection.style.display = 'block';
    }
}

/**
 * Handles adding the selected course and section via AJAX.
 */
function addSelectedCourse() {
    if (!selectedCourse) {
        alert('Please select a course first.');
        return;
    }

    const section = courseSectionSelect ? courseSectionSelect.value.trim() : '';
    if (!section) {
        alert('Please select a section.');
        return;
    }

    const coursesList = document.querySelector('.courses-list');
    // Check for existing courses to prevent duplicates in UI
    const existingCourses = Array.from(coursesList.querySelectorAll('.course-card')).map(card => {
        const name = card.querySelector('.course-name') ? card.querySelector('.course-name').textContent.trim() : '';
        const secText = card.querySelector('.course-section') ? card.querySelector('.course-section').textContent.trim() : '';
        const secMatch = secText.match(/Section (.*)/);
        const sec = secMatch ? secMatch[1].trim() : '';
        return { course_name: name, section: sec };
    });

    const courseExists = existingCourses.some(c => c.course_name.toLowerCase() === selectedCourse.toLowerCase() && c.section.toLowerCase() === section.toLowerCase());
    if (courseExists) {
        alert('This course with the selected section has already been added to your profile.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add_course');
    formData.append('course_name', selectedCourse);
    formData.append('section', section);

    // Show loading state on button
    if (confirmAddCourseButton) {
        const originalText = confirmAddCourseButton.innerHTML;
        confirmAddCourseButton.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Adding...';
        confirmAddCourseButton.disabled = true;

        fetch('profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add the course card to the UI dynamically
                addCourseCard(selectedCourse, section);
                // Close the modal
                closeCourseModal();
                alert('Course added successfully!'); // Provide feedback
            } else {
                alert('Error adding course: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error adding course via Fetch:', error);
            alert('An error occurred while adding the course');
        })
        .finally(() => {
            // Reset button state
            confirmAddCourseButton.innerHTML = originalText;
            confirmAddCourseButton.disabled = false;
        });
    }
}

/**
 * Dynamically adds a new course card to the UI.
 * @param {string} courseName - The name of the course.
 * @param {string} section - The section of the course.
 */
function addCourseCard(courseName, section) {
    const coursesList = document.querySelector('.courses-list');
    if (!coursesList) { console.error("Courses list container not found."); return; }
    const card = document.createElement('div');
    card.className = 'course-card';

    // Escape courseName and section for proper use in onclick attribute
    const escapedCourseName = courseName.replace(/'/g, "\\'");
    const escapedSection = section.replace(/'/g, "\\'");

    card.innerHTML = `
        <div class="course-info">
            <span class="course-name"><i class="mdi mdi-book-open-variant"></i> ${htmlspecialchars(courseName)}</span>
            <span class="course-section"><i class="mdi mdi-tag"></i> Section ${htmlspecialchars(section)}</span>
        </div>
        <button type="button" class="remove-button remove-icon" onclick="removeCourse('${escapedCourseName}', '${escapedSection}')">
            <i class="mdi mdi-close"></i>
        </button>
    `;
    coursesList.appendChild(card);
}

/**
 * Handles removing a course via AJAX.
 * @param {string} course - The name of the course to remove.
 * @param {string} section - The section of the course to remove.
 */
function removeCourse(course, section) {
  if (confirm('Are you sure you want to remove this course? This will affect your routine display.')) {
    const formData = new FormData();
    formData.append('action', 'remove_course');
    formData.append('course_name', course);
    formData.append('section', section); // Pass section for accurate removal

    fetch('profile.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Remove the course card from the UI
        const courseCards = document.querySelectorAll('.course-card');
        courseCards.forEach(card => {
          const cardCourseName = card.querySelector('.course-name') ? card.querySelector('.course-name').textContent.trim() : '';
          const cardSectionText = card.querySelector('.course-section') ? card.querySelector('.course-section').textContent.trim() : '';
          const cardSectionMatch = cardSectionText.match(/Section (.*)/);
          const cardSection = cardSectionMatch ? cardSectionMatch[1].trim() : '';

          if (cardCourseName === course && cardSection === section) {
            card.remove();
          }
        });
        alert('Course removed successfully!'); // Provide feedback
      } else {
        alert('Error removing course: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Fetch error removing course:', error);
      alert('An error occurred while removing the course.');
    });
  }
}

/**
 * Simple HTML escaping utility (to prevent XSS when injecting user-controlled text into HTML).
 * @param {string} text - The raw text to escape.
 * @returns {string} - The escaped HTML string.
 */
function htmlspecialchars(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}


// --- Event Listeners ---
document.addEventListener('DOMContentLoaded', function() {
    // Event listener for opening skill modal
    const addSkillButton = document.querySelector('.content-section .section-header button[onclick="showSkillModal()"]');
    if (addSkillButton) {
        addSkillButton.onclick = showSkillModal; // Assign corrected function
    }

    // Event listener for opening course modal
    const addCourseButton = document.querySelector('.content-section .section-header button[onclick="showCourseModal()"]');
    if (addCourseButton) {
        addCourseButton.onclick = showCourseModal; // Assign corrected function
    }

    // Close modal when clicking the close button
    if (closeCourseButton) closeCourseButton.onclick = closeCourseModal;
    if (closeSkillButton) closeSkillButton.onclick = closeSkillModal;

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == courseModal) {
            closeCourseModal();
        }
        if (event.target == skillModal) {
            closeSkillModal();
        }
    }

    // Course search functionality with debounce
    let courseSearchTimeout;
    if (courseSearchInput) {
        courseSearchInput.addEventListener('input', function(e) {
            clearTimeout(courseSearchTimeout);
            courseSearchTimeout = setTimeout(() => {
                // When search input changes, re-filter the already loaded courses
                displayAvailableCourses(allAvailableCourses); // Re-run display with current search term
                // Also, if search term changes, deselect any previously selected item
                selectedCourse = null;
                document.querySelectorAll('#available-courses-list .course-item-selectable.selected').forEach(item => {
                    item.classList.remove('selected');
                });
            }, 300);
        });
    }

    // Confirm add course button
    if (confirmAddCourseButton) confirmAddCourseButton.onclick = addSelectedCourse;

    // Add skill when clicking the confirm button
    if (confirmAddSkillButton) confirmAddSkillButton.onclick = addSelectedSkill;

    // Allow adding skill by pressing Enter in the input field
    if (skillNameInput) {
        skillNameInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Prevent form submission
                addSelectedSkill();
            }
        });
    }

    // Add script for profile picture preview
    const profilePictureInput = document.getElementById('profile_picture');
    const profilePictureImg = document.getElementById('profile-picture-display');

    if (profilePictureInput && profilePictureImg) {
        profilePictureInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePictureImg.src = e.target.result;
                }
                reader.onerror = function(e) {
                    console.error('FileReader error:', e);
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Event listener for the main profile form submission (Save All Changes)
    const profileForm = document.querySelector('.profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission

            const form = e.target;
            const formData = new FormData(form);

            // Manually add skills data to the FormData
            const skills = [];
            document.querySelectorAll('.skills-list .skill-card .skill-name').forEach(item => {
                skills.push(item.textContent.trim());
            });
            formData.append('skills', JSON.stringify(skills));

            // Manually add courses data to the FormData
            const courses = [];
            document.querySelectorAll('.courses-list .course-card').forEach(card => {
                const courseName = card.querySelector('.course-name') ? card.querySelector('.course-name').textContent.trim() : '';
                const sectionText = card.querySelector('.course-section') ? card.querySelector('.course-section').textContent.trim() : '';
                const sectionMatch = sectionText.match(/Section (.*)/);
                const section = sectionMatch ? sectionMatch[1].trim() : '';
                courses.push({ course_name: courseName, section: section });
            });
            formData.append('courses', JSON.stringify(courses));

            formData.append('action', 'save_all');

            // Show a loading indicator
            const saveButton = form.querySelector('.save-button');
            const originalButtonText = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Saving...';
            saveButton.disabled = true;

            // Send the data using Fetch API
            fetch('profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update profile picture displayed if it was changed
                    if (data.profile_picture) {
                        // Append a timestamp to the URL to bust cache
                        const newProfilePictureUrl = '../' + data.profile_picture + '?t=' + new Date().getTime();
                        
                        // Update the main profile picture
                        if (profilePictureImg) {
                            profilePictureImg.src = newProfilePictureUrl;
                        }
                        
                        // Also update the header profile picture
                        const headerProfileImg = document.querySelector('.header .notification img.profile-img');
                        if (headerProfileImg) {
                            headerProfileImg.src = newProfilePictureUrl;
                        }

                        // Update local session (if storing in client-side storage like localStorage) if needed
                    }
                    
                    alert('Profile updated successfully!'); // Or display a more user-friendly message
                    // Optionally reload the page to ensure all elements are updated (e.g., completion %)
                    window.location.reload(); 
                    
                } else {
                    alert('Error updating profile: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error during profile save:', error);
                alert('An error occurred while saving your profile.');
            })
            .finally(() => {
                // Restore button state
                saveButton.innerHTML = originalButtonText;
                saveButton.disabled = false;
            });
        });
    }
});
