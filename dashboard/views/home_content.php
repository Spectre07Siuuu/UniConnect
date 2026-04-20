        <div class="content-main">
          <div class="content-left">
            <div class="create-post">
              <form id="create-post-form" method="POST" enctype="multipart/form-data">
                <div class="post-input">
                  <div class="post-input-content">
                    <textarea
                      id="post-text-content" name="post_text"
                      placeholder="What's on your mind?"
                      aria-label="Create a post" required
                    ></textarea>
                    <div class="post-options">
                      <select id="post-category" name="category" aria-label="Select post category" required>
                        <option value="general" selected>General</option>
                        <option value="academic">Academic</option>
                        <option value="buy-sell">Buy & Sell</option>
                        <option value="lost-found">Lost & Found</option>
                      </select>
                      <label class="photo-upload new-photo-upload-btn">
                        <i class="mdi mdi-image"></i>
                        <span>Photo</span>
                        <input
                          type="file" id="post-image-upload" name="post_image"
                          accept="image/*"
                          aria-label="Upload photo"
                        />
                      </label>
                      <button type="submit" id="post-submit-button" class="post-button">Post</button>
                    </div>
                  </div>
                </div>
              </form>
            </div>

            <div class="posts-feed">
              <h3>Recent Posts</h3>
              <p class="no-recent-posts-message" style="text-align: center; color: var(--text-secondary); margin-top: 20px; font-size: 1.6rem;">No recent posts available. Be the first to post!</p>
            </div>
          </div>

          <div class="right-side-container">
            <div class="dynamic-monthly-calendar-block">
                <?php echo $monthly_calendar_html; ?>
            </div>

            <div class="class-routine">
              <h3>Your Class Routine</h3>
              <div class="class-list">
                <?php echo $class_routine_html; ?>
              </div>
            </div>
          </div>
        </div>
