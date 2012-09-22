using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Threading.Tasks;
using System.Windows.Forms;
using PhpCloudBoxClientLibrary;

namespace PhpCloudBoxClient
{
	static class Program
	{
		public class LoginInfo
		{
			public string Url;
			public string User;
			public string Password;
		}

		static public string LocateLoginFile()
		{
			string BasePath = "login";
			for (int n = 0; n < 10; n++)
			{
				if (File.Exists(BasePath)) return BasePath;
				BasePath = "../" + BasePath;
			}
			throw(new Exception("Can't find 'login' file"));
		}

		static public LoginInfo GetLoginInfo()
		{
			var Lines = File.ReadAllLines(LocateLoginFile());
			return new LoginInfo()
			{
				Url = Lines[0],
				User = Lines[1],
				Password = Lines[2],
			};
		}

		/// <summary>
		/// The main entry point for the application.
		/// </summary>
		[STAThread]
		static void Main()
		{
			//Application.EnableVisualStyles();
			//Application.SetCompatibleTextRenderingDefault(false);
			//Application.Run(new Form1());

			try
			{
				Task.Run(async () =>
				{
					var LoginInfo = GetLoginInfo();

					var Server = new PhpCloudBoxServer(LoginInfo.Url, LoginInfo.User, LoginInfo.Password);
					var Synchronizer = new PhpCloudFolderSynchronizer(Server, @"c:\temp\cloudbox");
					await Synchronizer.InitialDownloadAsync();
					Console.WriteLine("Connected");

					/*
					Console.WriteLine(String.Join("\n", (await Server.GetFileListAsync()).Select(Item => Item.path)));
					//await Server.DownloadFileAsync("test2.png");
					//await Server.DownloadFileToAsync("test2.png", @"c:\temp\lol.png");
					await Server.DeleteFileAsync("test.png");
					await Server.AddAndUploadFileAsync("temp.bmp", @"c:\temp\temp.bmp");
					*/

					Console.ReadKey();
				}).Wait();
			}
			catch (AggregateException AggregateException)
			{
				throw (AggregateException.InnerExceptions[0]);
			}
		}
	}
}
