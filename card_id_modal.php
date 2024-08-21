<!-- modal.php -->
<div class="modal fade" id="unregisteredCardModal" tabindex="-1" aria-labelledby="unregisteredCardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unregisteredCardModalLabel">Unregistered Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="registerCardForm" method="POST" action="register_card.php">
                    <div class="mb-3">
                        <label for="selectUser" class="form-label">Select User</label>
                        <select class="form-select" id="selectUser" name="user_id" required>
                            <option value="" disabled selected>Choose a user</option>
                            <?php
                            $query = "SELECT id, name FROM users WHERE cards_uid IS NULL";
                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<option value='{$row['id']}'>{$row['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="inputCardUid" class="form-label">Card UID</label>
                        <input type="text" class="form-control" id="inputCardUid" name="cards_uid" readonly>
                    </div>
                    <button type="submit" class="btn btn-primary">Register Card</button>
                </form>
            </div>
        </div>
    </div>
</div>
