        <?php if ($updateSuccess): ?>
          <div class="alert success"><i class="mdi mdi-check-circle"></i> <?php echo htmlspecialchars($updateSuccess); ?></div>
        <?php endif; ?>

        <?php if ($updateError): ?>
          <div class="alert error"><i class="mdi mdi-alert-circle"></i> <?php echo htmlspecialchars($updateError); ?></div>
        <?php endif; ?>

        <?php if ($uploadError): ?>
          <div class="alert error"><i class="mdi mdi-alert-circle"></i> <?php echo htmlspecialchars($uploadError); ?></div>
        <?php endif; ?>

        <div class="profile-container">
          <form method="POST" enctype="multipart/form-data" class="profile-form">
            <div class="content-section">
            <div class="profile-header">
              <div class="profile-picture-container">
                <img
                  src="<?php echo htmlspecialchars($profile_picture); ?>"
                  alt="Profile Picture"
                  class="profile-picture"
                  id="profile-picture-display"
                  onerror="this.src='../images/uniconnect.png';"
                />
                <div class="profile-picture-upload">
                  <label for="profile_picture" class="upload-btn">
                    <i class="mdi mdi-camera"></i>
                    </label>
                  <input
                    type="file"
                    id="profile_picture"
                    name="profile_picture"
                    accept="image/*"
                    style="display: none;"
                  />
                </div>
              </div>
              <div class="profile-info">
                <h2><?php echo htmlspecialchars($full_name); ?></h2>
                  <div class="info-grid">
                    <div class="info-item">
                      <label>Department</label>
                      <span><?php echo htmlspecialchars($department); ?></span>
                    </div>
                    <div class="info-item">
                      <label>Student ID</label>
                      <span><?php echo htmlspecialchars($student_id); ?></span>
                    </div>
                    <div class="info-item">
                      <label>Trimester</label>
                      <input type="text" name="trimester" class="text-field-styled" value="<?php echo htmlspecialchars($trimester); ?>">
                    </div>
                    <div class="info-item">
                      <label>Profile Completion</label>
                      <div class="completion-bar">
                        <div
                          class="completion-progress"
                          style="width: <?php echo $profile_completion; ?>%"
                        ></div>
                      </div>
                      <span class="completion-text"><?php echo $profile_completion; ?>% Complete
                        <?php if ($profile_completion === 100 && $reward_claimed): ?>
                          <span class="reward-badge"><i class="mdi mdi-medal"></i> Reward Claimed!</span>
                        <?php endif; ?>
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="completion-status-section" style="margin-top: 25px;">
                <h4>Completion Breakdown:</h4>
                <div class="completion-status">
                    <?php foreach ($completion_status as $key => $status_item): ?>
                        <div class="completion-item <?php echo $status_item['completed'] ? 'completed' : ''; ?>">
                            <i class="mdi <?php echo $status_item['icon']; ?>"></i>
                            <span><?php echo $status_item['label']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div class="content-section">
              <div class="section-header">
                <h3><i class="mdi mdi-account-details"></i> Bio</h3>
                </div>
              <textarea name="bio" class="bio-area" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($bio); ?></textarea>
              </div>

            <div class="content-section">
              <div class="section-header">
                <h3><i class="mdi mdi-star"></i> Skills</h3>
                <button type="button" class="action-button" onclick="showSkillModal()">
                  <i class="mdi mdi-plus"></i>
                  Add Skill
                </button>
              </div>
              <div class="skills-list">
                <?php foreach ($skills_data as $skill): ?>
                  <div class="skill-card" data-skill-name="<?php echo htmlspecialchars($skill); ?>">
                    <span class="skill-name"><i class="mdi mdi-check-bold"></i> <?php echo htmlspecialchars($skill); ?></span>
                    <button type="button" class="remove-button remove-icon">
                      <i class="mdi mdi-close"></i>
                    </button>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="content-section">
              <div class="section-header">
                <h3><i class="mdi mdi-book"></i> Courses</h3>
                <button type="button" class="action-button" onclick="showCourseModal()">
                  <i class="mdi mdi-plus"></i>
                  Add Course
                </button>
              </div>
              <div class="courses-list">
                <?php foreach ($courses_data as $course): ?>
                  <div class="course-card" data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>" data-section="<?php echo htmlspecialchars($course['section']); ?>">
                    <div class="course-info">
                      <span class="course-name"><i class="mdi mdi-book-open-variant"></i> <?php echo htmlspecialchars($course['course_name']); ?></span>
                      <span class="course-section"><i class="mdi mdi-tag"></i> Section <?php echo htmlspecialchars($course['section']); ?></span>
                    </div>
                    <button type="button" class="remove-button remove-icon">
                      <i class="mdi mdi-close"></i>
                    </button>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="save-changes">
              <button type="submit" class="save-button">
                <i class="mdi mdi-content-save"></i>
                Save All Changes
              </button>
            </div>
          </form>
        </div>

    <div id="course-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Course</h3>
                <span class="close-button">&times;</span>
            </div>
            <div class="modal-body">
                <div class="search-container">
                    <input type="text" id="course-search" placeholder="Search courses...">
                </div>
                <div id="available-courses-list">
                    </div>
                <div id="course-section-selection" style="display: none;">
                    <h4>Select Section:</h4>
                    <select id="course-section" class="section-select">
                        <option value="">Select Section</option> <option value="A">Section A</option>
                        <option value="B">Section B</option>
                        <option value="C">Section C</option>
                        <option value="D">Section D</option>
                        <option value="E">Section E</option>
                        <option value="F">Section F</option>
                        <option value="G">Section G</option>
                        </select>
                    <button id="confirm-add-course" class="add-course-btn">
                        <i class="mdi mdi-plus"></i>
                        Add Course
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="skill-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Skill</h3>
                <span class="close-button skill-close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="skill-name">Skill Name:</label>
                    <input type="text" id="skill-name" class="text-field-styled" placeholder="Enter skill name">
                </div>
                <button id="confirm-add-skill" class="add-skill-btn">
                    <i class="mdi mdi-plus"></i>
                    Add Skill
                </button>
            </div>
        </div>
    </div>
