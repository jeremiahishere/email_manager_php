<?php
include("includes/conn.php");
include("emailManager.php");

$file_exec_query = Emailer::get_file_exec_query_with_filter($CDB, $_GET['file_name'], $_GET['exec_time']);
?>

<?php include("includes/page-header.php"); ?>
<a href='fileexecmgr.php'>View All File Executions</a><br />
<form action='fileexecmgr.php' action='get'>
  Search by file name: <input type='text' id='file_name' name='file_name' /><br />
  Search by execution time: <input type='text' id='exec_time' name='exec_time' /><br />
  <input type='submit' value='Submit' />
</form>
<table>
<tbody>
  <tr>
    <th>File Name</th>
    <th>Execution Time</th>
    <th>Comment</th>
    <th>Emails</thl>
  </tr>
  <?php while($file_exec = mysql_fetch_array($file_exec_query)) : ?>
    <tr>
      <td><?php echo htmlentities($file_exec['file_name']); ?></td>
      <td><?php echo htmlentities($file_exec['start_time']); ?></td>
      <td><?php echo htmlentities($file_exec['file_comment']); ?></td>
      <td><a href='emailmgr.php?file_exec_id=<?php echo $file_exec['file_exec_id'] ?>'>emails(<?php echo $file_exec['email_count']; ?>)</a></td>
    </tr>
  <?php endwhile; ?>
</tbody>
</table>
<?php include("includes/page-footer.php"); ?>

