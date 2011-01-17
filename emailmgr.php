<?php
include("includes/emailManager.php");
include("includes/conn.php");
if(isset($_GET['file_exec_id']))  {
  $emails_query = Emailer::get_emails_by_file_exec_id_query($CDB, $_GET['file_exec_id']);
} else if(isset($_GET['email_id']))  {
  $emails_query = Emailer::get_emails_by_email_id_query($CDB, $_GET['email_id']);
}
?>

<?php include("includes/page-header.php"); ?>
<a href='fileexecmgr.php'>Return to File Exec Manager</a>
<table>
<tbody>
  <tr>
    <th>To</th>
    <th>Subject</th>
    <th>Message</th>
    <th>View</thl>
  </tr>
  <?php while($email = mysql_fetch_array($emails_query)) : ?>
    <tr>
      <td><?php echo htmlentities($email['mail_to']); ?></td>
      <td><?php echo htmlentities($email['subject']); ?></td>
      <!--<td><?php echo htmlentities(substr($email['message'], 0, 256)); if(strlen($email['message']) > 256) { echo "..."; } ?></td>-->
      <td><a href='emailview.php?email_id=<?php echo $email['email_id'] ?>'>view</a></td>
    </tr>
  <?php endwhile; ?>
</tbody>
</table>
<?php include("includes/page-footer.php"); ?>
